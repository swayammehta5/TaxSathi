'use client';

import { useTranslations } from 'next-intl';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import api from '@/lib/axios';
import { Trash2 } from 'lucide-react';
import { useAuthStore } from '@/store/useAuthStore';

function normalizeEmployee(row) {
  const id = row?.id ?? row?._id;
  return id != null ? { ...row, id: String(id) } : row;
}

function normalizeEmployees(data) {
  let list = [];
  if (Array.isArray(data)) list = data;
  else if (Array.isArray(data?.data)) list = data.data;
  else if (data?.employee) list = [data.employee];
  return list.map(normalizeEmployee).filter((e) => e?.id);
}

export default function EmployeesPage() {
  const t = useTranslations('Employees');
  const { user } = useAuthStore();
  const [employees, setEmployees] = useState([]);
  const [loading, setLoading] = useState(true);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [role, setRole] = useState('staff');

  const fetchEmployees = useCallback(
    async ({ silent = false } = {}) => {
      if (user?.role !== 'admin') {
        setLoading(false);
        return;
      }
      if (!silent) setLoading(true);
      try {
        const { data } = await api.get('/employees');
        const list = normalizeEmployees(data);
        console.log('Employees:', list);
        setEmployees(list);
      } catch {
        toast.error(t('loadFail'));
        if (!silent) setEmployees([]);
      } finally {
        if (!silent) setLoading(false);
      }
    },
    [t, user?.role]
  );

  useEffect(() => {
    fetchEmployees();
  }, [fetchEmployees]);

  const submit = async (e) => {
    e.preventDefault();
    try {
      const { data } = await api.post('/employees', { name: name.trim(), email: email.trim(), role });
      const newEmployee = data?.employee ?? data;
      toast.success(t('added'));
      setName('');
      setEmail('');
      setRole('staff');
      if (newEmployee?.id) {
        setEmployees((prev) => {
          const exists = prev.some((r) => String(r.id) === String(newEmployee.id));
          const next = exists
            ? prev
            : [...prev, newEmployee].sort((a, b) => a.name.localeCompare(b.name));
          console.log('Employees:', next);
          return next;
        });
      }
      await fetchEmployees({ silent: true });
    } catch (err) {
      const errors = err?.response?.data?.errors;
      if (errors && typeof errors === 'object') {
        const first = Object.values(errors).flat()[0];
        toast.error(first ? String(first) : t('loadFail'));
      } else {
        toast.error(err?.response?.data?.message ?? t('loadFail'));
      }
    }
  };

  const deleteEmployee = async (id) => {
    const employeeId = String(id);
    console.log('delete employee id:', employeeId);
    if (!employeeId) {
      toast.error(t('loadFail'));
      return;
    }
    if (!confirm(t('removeConfirm'))) return;
    try {
      await api.delete(`/employees/${encodeURIComponent(employeeId)}`);
      toast.success(t('removed'));
      setEmployees((prev) => prev.filter((r) => String(r.id) !== employeeId));
      await fetchEmployees({ silent: true });
    } catch (err) {
      console.error(err);
      toast.error(err?.response?.data?.message ?? t('loadFail'));
    }
  };

  if (user?.role !== 'admin') {
    return (
      <div className="max-w-lg">
        <h1 className="text-2xl font-bold text-slate-800 dark:text-white mb-2">{t('title')}</h1>
        <p className="text-slate-600 dark:text-slate-400">{t('forbidden')}</p>
      </div>
    );
  }

  return (
    <div>
      <h1 className="text-3xl font-bold text-slate-800 dark:text-white mb-2">{t('title')}</h1>
      <p className="text-slate-600 dark:text-slate-400 mb-8">{t('subtitle')}</p>

      <div className="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 mb-8 max-w-xl">
        <h2 className="font-semibold text-slate-800 dark:text-white mb-4">{t('addTitle')}</h2>
        <form onSubmit={submit} className="space-y-4">
          <div>
            <label className="block text-sm text-slate-600 mb-1">{t('name')}</label>
            <input
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900"
            />
          </div>
          <div>
            <label className="block text-sm text-slate-600 mb-1">{t('email')}</label>
            <input
              required
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900"
            />
          </div>
          <div>
            <label className="block text-sm text-slate-600 mb-1">{t('role')}</label>
            <select
              value={role}
              onChange={(e) => setRole(e.target.value)}
              className="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900"
            >
              <option value="staff">{t('staff')}</option>
              <option value="admin">{t('admin')}</option>
            </select>
          </div>
          <button
            type="submit"
            className="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700"
          >
            {t('addButton')}
          </button>
        </form>
      </div>

      <div className="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="bg-slate-50 dark:bg-slate-900/50 text-slate-500">
              <tr>
                <th className="p-3">{t('colName')}</th>
                <th className="p-3">{t('colEmail')}</th>
                <th className="p-3">{t('colRole')}</th>
                <th className="p-3">{t('colAdded')}</th>
                <th className="p-3 text-right"> </th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={5} className="p-8 text-center text-slate-500">
                    …
                  </td>
                </tr>
              ) : employees.length === 0 ? (
                <tr>
                  <td colSpan={5} className="p-8 text-center text-slate-500">
                    —
                  </td>
                </tr>
              ) : (
                employees.map((employee) => (
                  <tr key={String(employee.id)} className="border-t border-slate-100 dark:border-slate-800">
                    <td className="p-3 font-medium">{employee.name}</td>
                    <td className="p-3">{employee.email}</td>
                    <td className="p-3 capitalize">{employee.role}</td>
                    <td className="p-3 text-slate-500">
                      {employee.created_at ? new Date(employee.created_at).toLocaleDateString() : '—'}
                    </td>
                    <td className="p-3 text-right">
                      <button
                        type="button"
                        onClick={() => deleteEmployee(employee.id)}
                        disabled={String(employee.id) === String(user?.id ?? '')}
                        className="p-2 text-red-600 disabled:opacity-30 rounded hover:bg-red-50 dark:hover:bg-red-950/30"
                        aria-label={t('remove')}
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
