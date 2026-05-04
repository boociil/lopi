import React, { useState, useMemo } from 'react';
import {
  LayoutDashboard,
  Plane,
  Clock,
  Users,
  Plus,
  Search,
  ChevronRight,
  FileText,
  CheckCircle2,
  AlertCircle,
  Calendar,
  MapPin,
  DollarSign,
  Info
} from 'lucide-react';

const App = () => {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [showForm, setShowForm] = useState(null);

  // Data State
  const [perjalananDinas, setPerjalananDinas] = useState([]);
  const [lembur, setLembur] = useState([]);
  const [rapat, setRapat] = useState([]);

  // Form Handlers
  const handleAddData = (type, data) => {
    const newData = { ...data, id: Date.now(), createdAt: new Date().toISOString() };
    if (type === 'perjalanan') setPerjalananDinas([...perjalananDinas, newData]);
    if (type === 'lembur') setLembur([...lembur, newData]);
    if (type === 'rapat') setRapat([...rapat, newData]);
    setShowForm(null);
  };

  // Stats
  const stats = [
    { title: 'Perjalanan Dinas', count: perjalananDinas.length, icon: Plane, color: 'bg-blue-500', tab: 'perjalanan' },
    { title: 'Lembur', count: lembur.length, icon: Clock, color: 'bg-orange-500', tab: 'lembur' },
    { title: 'Rapat', count: rapat.length, icon: Users, color: 'bg-purple-500', tab: 'rapat' },
  ];

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col md:flex-row">
      {/* Sidebar */}
      <aside className="w-full md:w-64 bg-slate-900 text-white p-6">
        <div className="flex items-center gap-3 mb-10">
          <div className="p-2 bg-blue-600 rounded-lg">
            <LayoutDashboard size={24} />
          </div>
          <h1 className="text-xl font-bold tracking-tight">AdminSystem</h1>
        </div>

        <nav className="space-y-2">
          <NavItem
            icon={<LayoutDashboard size={20}/>}
            label="Dashboard"
            active={activeTab === 'dashboard'}
            onClick={() => setActiveTab('dashboard')}
          />
          <div className="pt-4 pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Modul</div>
          <NavItem
            icon={<Plane size={20}/>}
            label="Perjalanan Dinas"
            active={activeTab === 'perjalanan'}
            onClick={() => setActiveTab('perjalanan')}
          />
          <NavItem
            icon={<Clock size={20}/>}
            label="Lembur"
            active={activeTab === 'lembur'}
            onClick={() => setActiveTab('lembur')}
          />
          <NavItem
            icon={<Users size={20}/>}
            label="Rapat"
            active={activeTab === 'rapat'}
            onClick={() => setActiveTab('rapat')}
          />
        </nav>
      </aside>

      {/* Main Content */}
      <main className="flex-1 p-4 md:p-8 overflow-auto">
        <header className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
          <div>
            <h2 className="text-2xl font-bold text-slate-800">
              {activeTab === 'dashboard' ? 'Ringkasan Dashboard' :
               activeTab === 'perjalanan' ? 'Manajemen Perjalanan Dinas' :
               activeTab === 'lembur' ? 'Manajemen Lembur' : 'Manajemen Rapat'}
            </h2>
            <p className="text-slate-500">Selamat datang kembali, berikut data administrasi Anda.</p>
          </div>

          {activeTab !== 'dashboard' && (
            <button
              onClick={() => setShowForm(activeTab)}
              className="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-all shadow-md"
            >
              <Plus size={20} />
              Tambah Data
            </button>
          )}
        </header>

        {/* Dashboard Grid */}
        {activeTab === 'dashboard' && (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {stats.map((stat, i) => (
              <div
                key={i}
                onClick={() => setActiveTab(stat.tab)}
                className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 cursor-pointer hover:shadow-md transition-shadow group"
              >
                <div className="flex justify-between items-start">
                  <div className={`p-3 rounded-lg ${stat.color} text-white shadow-lg`}>
                    <stat.icon size={24} />
                  </div>
                  <ChevronRight className="text-slate-300 group-hover:text-slate-500 transition-colors" />
                </div>
                <div className="mt-4">
                  <h3 className="text-slate-500 font-medium">{stat.title}</h3>
                  <p className="text-3xl font-bold text-slate-800">{stat.count}</p>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Module Content */}
        {activeTab !== 'dashboard' && (
          <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div className="p-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
              <div className="relative w-full max-w-xs">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                <input
                  type="text"
                  placeholder="Cari data..."
                  className="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                />
              </div>
            </div>

            <div className="overflow-x-auto">
              <TableContent
                type={activeTab}
                data={activeTab === 'perjalanan' ? perjalananDinas : activeTab === 'lembur' ? lembur : rapat}
              />
            </div>
          </div>
        )}

        {/* Form Modals */}
        {showForm && (
          <FormModal
            type={showForm}
            onClose={() => setShowForm(null)}
            onSubmit={(data) => handleAddData(showForm, data)}
          />
        )}
      </main>
    </div>
  );
};

// Sub-components
const NavItem = ({ icon, label, active, onClick }) => (
  <button
    onClick={onClick}
    className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all ${
      active ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
    }`}
  >
    {icon}
    <span className="font-medium text-sm">{label}</span>
  </button>
);

const TableContent = ({ type, data }) => {
  if (data.length === 0) {
    return (
      <div className="p-20 text-center text-slate-400">
        <FileText size={48} className="mx-auto mb-4 opacity-20" />
        <p>Belum ada data tersedia.</p>
      </div>
    );
  }

  return (
    <table className="w-full text-left border-collapse">
      <thead>
        <tr className="text-xs uppercase text-slate-500 bg-slate-50 border-b border-slate-200">
          <th className="px-6 py-4 font-semibold">No</th>
          <th className="px-6 py-4 font-semibold">Deskripsi</th>
          <th className="px-6 py-4 font-semibold">Pelaksana</th>
          <th className="px-6 py-4 font-semibold">Tanggal</th>
          <th className="px-6 py-4 font-semibold">Status</th>
          <th className="px-6 py-4 font-semibold">PIC / No FP</th>
          <th className="px-6 py-4 font-semibold">Keuangan (SP2D)</th>
        </tr>
      </thead>
      <tbody className="divide-y divide-slate-100 text-sm text-slate-700">
        {data.map((item, index) => (
          <tr key={item.id} className="hover:bg-slate-50 transition-colors">
            <td className="px-6 py-4 font-medium text-slate-400">{index + 1}</td>
            <td className="px-6 py-4">
              <div className="font-semibold text-slate-900">{item.deskripsi || item.deskripsiLembur}</div>
              <div className="text-xs text-slate-500 mt-1">{item.tujuan || item.tempat || 'Internal'}</div>
            </td>
            <td className="px-6 py-4">{item.pelaksanaSPD || item.pelaksanaLembur || item.namaPanitia}</td>
            <td className="px-6 py-4">
              <div className="flex items-center gap-2">
                <Calendar size={14} className="text-slate-400" />
                {item.tanggalPelaksanaan}
              </div>
            </td>
            <td className="px-6 py-4">
              <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                item.status.includes('Selesai') ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'
              }`}>
                {item.status}
              </span>
            </td>
            <td className="px-6 py-4">
              <div className="font-medium">{item.pic}</div>
              <div className="text-xs text-blue-600">{item.noFP}</div>
            </td>
            <td className="px-6 py-4">
              <div className="text-xs font-semibold">{item.noSP2D || '-'}</div>
              <div className="text-xs text-green-600">{item.nilaiSP2D ? `Rp ${Number(item.nilaiSP2D).toLocaleString()}` : '-'}</div>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};

const FormModal = ({ type, onClose, onSubmit }) => {
  const [formData, setFormData] = useState({
    status: 'Draft',
    persetujuan: 'Menunggu',
    pic: 'Admin',
    noFP: '',
    noSP2D: '',
    nilaiSP2D: '',
    tanggalPencairan: '',
    catatan: ''
  });

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const titles = {
    perjalanan: 'Form Perjalanan Dinas',
    lembur: 'Form Lembur',
    rapat: 'Form Rapat'
  };

  return (
    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <div className="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <h3 className="text-xl font-bold text-slate-800">{titles[type]}</h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600">×</button>
        </div>

        <form onSubmit={(e) => { e.preventDefault(); onSubmit(formData); }} className="p-6 overflow-auto flex-1 grid grid-cols-1 md:grid-cols-2 gap-6">

          {/* Section 1: General Info */}
          <div className="space-y-4">
            <h4 className="font-bold text-sm text-blue-600 uppercase flex items-center gap-2">
              <Info size={16} /> Informasi Utama
            </h4>

            <div className="space-y-2">
              <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Deskripsi Permintaan</label>
              <textarea
                name={type === 'lembur' ? 'deskripsiLembur' : 'deskripsi'}
                onChange={handleChange} required
                className="w-full p-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                placeholder="Masukkan detail permintaan..."
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal Pelaksanaan</label>
                <input type="date" name="tanggalPelaksanaan" onChange={handleChange} required className="w-full p-2 border border-slate-200 rounded-lg text-sm" />
              </div>
              <div className="space-y-2">
                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Beban Akun</label>
                <input type="text" name="bebanAkun" onChange={handleChange} className="w-full p-2 border border-slate-200 rounded-lg text-sm" />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pelaksana / Personel</label>
              <input
                type="text"
                name={type === 'perjalanan' ? 'pelaksanaSPD' : type === 'lembur' ? 'pelaksanaLembur' : 'namaPanitia'}
                onChange={handleChange} required
                className="w-full p-2 border border-slate-200 rounded-lg text-sm"
              />
            </div>

            {type === 'perjalanan' && (
              <div className="space-y-2">
                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tujuan</label>
                <input type="text" name="tujuan" onChange={handleChange} className="w-full p-2 border border-slate-200 rounded-lg text-sm" />
              </div>
            )}

            {type === 'rapat' && (
              <>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tempat</label>
                    <input type="text" name="tempat" onChange={handleChange} className="w-full p-2 border border-slate-200 rounded-lg text-sm" />
                  </div>
                  <div className="space-y-2">
                    <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jenis Rapat</label>
                    <input type="text" name="jenisRapat" onChange={handleChange} className="w-full p-2 border border-slate-200 rounded-lg text-sm" />
                  </div>
                </div>
              </>
            )}
          </div>

          {/* Section 2: Financial & Status */}
          <div className="space-y-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
            <h4 className="font-bold text-sm text-green-600 uppercase flex items-center gap-2">
              <DollarSign size={16} /> Keuangan & Administrasi
            </h4>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">No. FP</label>
                <input type="text" name="noFP" onChange={handleChange} className="w-full p-2 border border-slate-200 rounded-lg text-sm bg-white" />
              </div>
              <div className="space-y-2">
                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">No. SP2D</label>
                <input type="text" name="noSP2D" onChange={handleChange} className="w-full p-2 border border-slate-200 rounded-lg text-sm bg-white" />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Nilai SP2D</label>
                <input type="number" name="nilaiSP2D" onChange={handleChange} className="w-full p-2 border border-slate-200 rounded-lg text-sm bg-white" />
              </div>
              <div className="space-y-2">
                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tgl Pencairan</label>
                <input type="date" name="tanggalPencairan" onChange={handleChange} className="w-full p-2 border border-slate-200 rounded-lg text-sm bg-white" />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status Progres</label>
              <select name="status" onChange={handleChange} className="w-full p-2 border border-slate-200 rounded-lg text-sm bg-white">
                <option value="Entry BOS">Entry BOS</option>
                {type === 'perjalanan' && <option value="Terbit ST">Terbit ST</option>}
                {type === 'lembur' && <option value="Nota Dinas">Nota Dinas</option>}
                {type === 'rapat' && <option value="Undangan">Undangan</option>}
                <option value="Selesai">Selesai / Cair</option>
              </select>
            </div>

            <div className="space-y-2">
              <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Catatan</label>
              <textarea name="catatan" onChange={handleChange} className="w-full p-2 border border-slate-200 rounded-lg text-sm bg-white" rows="2" />
            </div>
          </div>

          <div className="md:col-span-2 pt-4 border-t border-slate-100 flex justify-end gap-3">
            <button type="button" onClick={onClose} className="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
            <button type="submit" className="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-200">Simpan Data</button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default App;
