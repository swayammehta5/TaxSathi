<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendInvoiceEmailRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Mail\InvoiceGeneratedMail;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request)
    {
        $query = Invoice::query()->with('firm')->orderByDesc('date');

        if ($request->filled('firm_id')) {
            $query->where('firm_id', (string) $request->input('firm_id'));
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->forMonth($request->integer('month'), $request->integer('year'));
        }

        if ($request->filled('direction')) {
            $query->where('direction', (string) $request->input('direction'));
        }

        return InvoiceResource::collection($query->paginate(15));
    }

    public function store(StoreInvoiceRequest $request)
    {
        Log::info('invoice.store', $request->all());

        try {
            $invoice = $this->invoiceService->create($request->validated());

            return response()->json([
                'invoice' => (new InvoiceResource($invoice))->resolve(),
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Unable to create invoice.',
            ], 500);
        }
    }

    public function show(string $id)
    {
        $invoice = Invoice::query()->with(['firm', 'items'])->findOrFail($id);

        return response()->json((new InvoiceResource($invoice))->resolve());
    }

    public function update(UpdateInvoiceRequest $request, string $id)
    {
        $invoice = Invoice::query()->findOrFail($id);

        try {
            $invoice = $this->invoiceService->update($invoice, $request->validated());

            return response()->json([
                'message' => 'Invoice updated successfully',
                'invoice' => (new InvoiceResource($invoice))->resolve(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Unable to update invoice.',
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $invoice = Invoice::query()->findOrFail($id);
        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully']);
    }

    public function sendEmail(SendInvoiceEmailRequest $request, string $id)
    {
        $invoice = Invoice::query()->with(['firm', 'items'])->findOrFail($id);

        Mail::to($request->validated('email'))
            ->queue(new InvoiceGeneratedMail($invoice));

        return response()->json(['message' => 'Invoice email sent successfully']);
    }

    public function download(string $invoice)
    {
        return $this->downloadPdf($invoice);
    }

    public function downloadPdf(string $invoice)
    {
        try {
            $model = Invoice::query()->with(['firm', 'items'])->findOrFail($invoice);

            $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $model->invoice_number ?? 'invoice').'.pdf';
            $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $model])->setPaper('a4');

            return response()->streamDownload(
                static function () use ($pdf): void {
                    echo $pdf->output();
                },
                $filename,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                    'Cache-Control' => 'private, max-age=0, must-revalidate',
                    'Pragma' => 'public',
                ]
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Could not generate PDF.',
            ], 500);
        }
    }
}
