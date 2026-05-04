import React, { useState, useRef, useEffect } from 'react';
import {
  Plus,
  Search,
  Package,
  User,
  History,
  CheckCircle2,
  AlertCircle,
  Camera,
  PenTool,
  ArrowRightLeft,
  X,
  Info
} from 'lucide-react';

// Simulasi Data Awal
const INITIAL_ITEMS = [
  {
    id: '1',
    nama: 'Kamera Sony Alpha A7III',
    kategori: 'Elektronik',
    penanggungJawab: 'Budi Santoso',
    status: 'Tersedia', // 'Tersedia' atau 'Dipinjam'
    peminjamSekarang: null,
    fotoUrl: 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=400&q=80',
    riwayat: []
  },
  {
    id: '2',
    nama: 'Projector Epson EB-X400',
    kategori: 'Elektronik',
    penanggungJawab: 'Siti Aminah',
    status: 'Dipinjam',
    peminjamSekarang: 'Andi Wijaya',
    fotoUrl: 'https://images.unsplash.com/photo-1535016120720-40c646bebbfc?w=400&q=80',
    riwayat: [
      {
        id: 'r1',
        peminjam: 'Andi Wijaya',
        waktuPinjam: '2023-10-25 09:00',
        fotoPinjam: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&q=80',
        ttdPinjam: 'data:image/png;base64,...'
      }
    ]
  },
  {
    id: '3',
    nama: 'Lensa Canon 50mm f/1.8',
    kategori: 'Aksesoris',
    penanggungJawab: 'Budi Santoso',
    status: 'Tersedia',
    peminjamSekarang: null,
    fotoUrl: 'https://images.unsplash.com/photo-1617005082133-548c4dd27f35?w=400&q=80',
    riwayat: []
  }
];

export default function App() {
  const [items, setItems] = useState(INITIAL_ITEMS);
  const [searchTerm, setSearchTerm] = useState('');
  const [activeTab, setActiveTab] = useState('inventory'); // inventory, history
  const [selectedItem, setSelectedItem] = useState(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalType, setModalType] = useState('pinjam'); // pinjam, kembali, detail

  // State for Form
  const [peminjamName, setPeminjamName] = useState('');
  const [capturedPhoto, setCapturedPhoto] = useState(null);
  const canvasRef = useRef(null);
  const [isDrawing, setIsDrawing] = useState(false);

  // Filter items
  const filteredItems = items.filter(item =>
    item.nama.toLowerCase().includes(searchTerm.toLowerCase()) ||
    item.penanggungJawab.toLowerCase().includes(searchTerm.toLowerCase())
  );

  // --- Signature Pad Logic ---
  const startDrawing = (e) => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX || e.touches[0].clientX) - rect.left;
    const y = (e.clientY || e.touches[0].clientY) - rect.top;

    const ctx = canvas.getContext('2d');
    ctx.beginPath();
    ctx.moveTo(x, y);
    setIsDrawing(true);
  };

  const draw = (e) => {
    if (!isDrawing) return;
    const canvas = canvasRef.current;
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX || (e.touches && e.touches[0].clientX)) - rect.left;
    const y = (e.clientY || (e.touches && e.touches[0].clientY)) - rect.top;

    const ctx = canvas.getContext('2d');
    ctx.lineTo(x, y);
    ctx.stroke();
    e.preventDefault();
  };

  const stopDrawing = () => {
    setIsDrawing(false);
  };

  const clearSignature = () => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
  };

  // --- Actions ---
  const handleAction = (item, type) => {
    setSelectedItem(item);
    setModalType(type);
    setIsModalOpen(true);
    // Reset form
    setPeminjamName('');
    setCapturedPhoto(null);
  };

  const submitTransaction = () => {
    if (modalType === 'pinjam') {
      if (!peminjamName || !capturedPhoto) {
        alert("Mohon lengkapi nama dan foto!");
        return;
      }

      const signature = canvasRef.current.toDataURL();

      const updatedItems = items.map(item => {
        if (item.id === selectedItem.id) {
          const newRecord = {
            id: Date.now().toString(),
            peminjam: peminjamName,
            waktuPinjam: new Date().toLocaleString('id-ID'),
            fotoPinjam: capturedPhoto,
            ttdPinjam: signature,
            waktuKembali: null,
            fotoKembali: null
          };
          return {
            ...item,
            status: 'Dipinjam',
            peminjamSekarang: peminjamName,
            riwayat: [newRecord, ...item.riwayat]
          };
        }
        return item;
      });
      setItems(updatedItems);
    } else if (modalType === 'kembali') {
      if (!capturedPhoto) {
        alert("Mohon ambil foto bukti pengembalian!");
        return;
      }

      const updatedItems = items.map(item => {
        if (item.id === selectedItem.id) {
          const newRiwayat = [...item.riwayat];
          newRiwayat[0] = {
            ...newRiwayat[0],
            waktuKembali: new Date().toLocaleString('id-ID'),
            fotoKembali: capturedPhoto
          };
          return {
            ...item,
            status: 'Tersedia',
            peminjamSekarang: null,
            riwayat: newRiwayat
          };
        }
        return item;
      });
      setItems(updatedItems);
    }

    setIsModalOpen(false);
  };

  const simulateCapture = () => {
    // Simulasi pengambilan foto karena kendala akses kamera di iframe tertentu
    const mockPhotos = [
      'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=200&q=80',
      'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=200&q=80',
      'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&q=80'
    ];
    setCapturedPhoto(mockPhotos[Math.floor(Math.random() * mockPhotos.length)]);
  };

  return (
    <div className="min-h-screen bg-slate-50 font-sans text-slate-900 pb-20">
      {/* Header */}
      <header className="bg-white border-b sticky top-0 z-10">
        <div className="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="bg-indigo-600 p-2 rounded-lg">
              <Package className="text-white w-6 h-6" />
            </div>
            <h1 className="text-xl font-bold tracking-tight">PinjamBarang.</h1>
          </div>
          <div className="flex bg-slate-100 p-1 rounded-lg">
            <button
              onClick={() => setActiveTab('inventory')}
              className={`px-4 py-1.5 rounded-md text-sm font-medium transition-all ${activeTab === 'inventory' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500'}`}
            >
              Inventaris
            </button>
            <button
              onClick={() => setActiveTab('history')}
              className={`px-4 py-1.5 rounded-md text-sm font-medium transition-all ${activeTab === 'history' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500'}`}
            >
              Riwayat Global
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-5xl mx-auto px-4 py-8">
        {activeTab === 'inventory' ? (
          <>
            {/* Search & Stats */}
            <div className="flex flex-col md:flex-row gap-4 mb-8">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                <input
                  type="text"
                  placeholder="Cari nama barang atau penanggung jawab..."
                  className="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
              <div className="flex gap-2">
                <div className="bg-emerald-50 text-emerald-700 px-4 py-2 rounded-xl border border-emerald-100 flex items-center gap-2">
                  <CheckCircle2 className="w-4 h-4" />
                  <span className="text-sm font-semibold">{items.filter(i => i.status === 'Tersedia').length} Tersedia</span>
                </div>
                <div className="bg-amber-50 text-amber-700 px-4 py-2 rounded-xl border border-amber-100 flex items-center gap-2">
                  <ArrowRightLeft className="w-4 h-4" />
                  <span className="text-sm font-semibold">{items.filter(i => i.status === 'Dipinjam').length} Dipinjam</span>
                </div>
              </div>
            </div>

            {/* Grid Inventaris */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {filteredItems.map(item => (
                <div key={item.id} className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                  <div className="relative h-48">
                    <img
                      src={item.fotoUrl}
                      alt={item.nama}
                      className="w-full h-full object-cover"
                    />
                    <div className={`absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm ${
                      item.status === 'Tersedia' ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white'
                    }`}>
                      {item.status}
                    </div>
                  </div>
                  <div className="p-5">
                    <div className="mb-4">
                      <p className="text-xs text-slate-500 font-medium uppercase mb-1">{item.kategori}</p>
                      <h3 className="text-lg font-bold text-slate-800 leading-tight">{item.nama}</h3>
                    </div>

                    <div className="space-y-3 mb-6">
                      <div className="flex items-center gap-3 text-sm">
                        <div className="bg-slate-100 p-1.5 rounded-lg">
                          <User className="w-4 h-4 text-slate-600" />
                        </div>
                        <div>
                          <p className="text-slate-400 text-[10px] uppercase font-bold">Penanggung Jawab</p>
                          <p className="font-medium">{item.penanggungJawab}</p>
                        </div>
                      </div>

                      <div className="flex items-center gap-3 text-sm">
                        <div className="bg-slate-100 p-1.5 rounded-lg">
                          <Info className="w-4 h-4 text-slate-600" />
                        </div>
                        <div>
                          <p className="text-slate-400 text-[10px] uppercase font-bold">Status Peminjam</p>
                          <p className={`font-medium ${item.peminjamSekarang ? 'text-amber-600' : 'text-slate-500'}`}>
                            {item.peminjamSekarang || 'Belum ada'}
                          </p>
                        </div>
                      </div>
                    </div>

                    <div className="flex gap-2 pt-4 border-t border-slate-50">
                      {item.status === 'Tersedia' ? (
                        <button
                          onClick={() => handleAction(item, 'pinjam')}
                          className="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center justify-center gap-2"
                        >
                          Pinjam Alat
                        </button>
                      ) : (
                        <button
                          onClick={() => handleAction(item, 'kembali')}
                          className="flex-1 bg-amber-600 hover:bg-amber-700 text-white py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center justify-center gap-2"
                        >
                          Kembalikan
                        </button>
                      )}
                      <button
                        onClick={() => handleAction(item, 'detail')}
                        className="bg-slate-100 hover:bg-slate-200 text-slate-600 p-2.5 rounded-xl transition-colors"
                      >
                        <History className="w-5 h-5" />
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </>
        ) : (
          <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table className="w-full text-left border-collapse">
              <thead className="bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Barang</th>
                  <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Peminjam</th>
                  <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Waktu Pinjam</th>
                  <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Waktu Kembali</th>
                  <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {items.flatMap(item => item.riwayat.map(log => ({ ...log, itemNama: item.nama }))).map((log, idx) => (
                  <tr key={idx} className="hover:bg-slate-50 transition-colors">
                    <td className="px-6 py-4 font-medium text-slate-800">{log.itemNama}</td>
                    <td className="px-6 py-4 text-slate-600">{log.peminjam}</td>
                    <td className="px-6 py-4 text-slate-500 text-sm">{log.waktuPinjam}</td>
                    <td className="px-6 py-4 text-slate-500 text-sm">{log.waktuKembali || '-'}</td>
                    <td className="px-6 py-4">
                      <span className={`px-2 py-1 rounded-full text-[10px] font-bold uppercase ${log.waktuKembali ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600'}`}>
                        {log.waktuKembali ? 'Selesai' : 'Aktif'}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </main>

      {/* Modal - Shadcn-like Implementation */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setIsModalOpen(false)}></div>
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-xl relative z-10 max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b sticky top-0 bg-white z-20 flex justify-between items-center">
              <div>
                <h2 className="text-xl font-bold text-slate-800">
                  {modalType === 'pinjam' && 'Form Peminjaman'}
                  {modalType === 'kembali' && 'Konfirmasi Pengembalian'}
                  {modalType === 'detail' && 'Detail & Riwayat Alat'}
                </h2>
                <p className="text-sm text-slate-500">{selectedItem?.nama}</p>
              </div>
              <button onClick={() => setIsModalOpen(false)} className="p-2 hover:bg-slate-100 rounded-full">
                <X className="w-5 h-5 text-slate-400" />
              </button>
            </div>

            <div className="p-6 space-y-6">
              {modalType === 'detail' ? (
                <div className="space-y-6">
                   <div className="grid grid-cols-2 gap-4">
                      <div className="p-4 bg-slate-50 rounded-2xl">
                        <p className="text-[10px] font-bold text-slate-400 uppercase mb-1">Penanggung Jawab Utama</p>
                        <p className="font-bold text-slate-800">{selectedItem.penanggungJawab}</p>
                      </div>
                      <div className="p-4 bg-slate-50 rounded-2xl">
                        <p className="text-[10px] font-bold text-slate-400 uppercase mb-1">Status Saat Ini</p>
                        <p className={`font-bold ${selectedItem.status === 'Tersedia' ? 'text-emerald-600' : 'text-amber-600'}`}>{selectedItem.status}</p>
                      </div>
                   </div>

                   <h3 className="font-bold text-slate-800 flex items-center gap-2">
                     <History className="w-4 h-4" /> Riwayat Terakhir
                   </h3>

                   <div className="space-y-4">
                     {selectedItem.riwayat.length === 0 ? (
                       <p className="text-center py-8 text-slate-400 italic text-sm">Belum ada riwayat peminjaman</p>
                     ) : (
                       selectedItem.riwayat.map((log, i) => (
                         <div key={i} className="border border-slate-100 rounded-2xl p-4 space-y-4">
                           <div className="flex justify-between items-start">
                             <div className="flex items-center gap-3">
                               <div className="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center font-bold text-indigo-600">
                                 {log.peminjam.charAt(0)}
                               </div>
                               <div>
                                 <p className="font-bold text-slate-800">{log.peminjam}</p>
                                 <p className="text-xs text-slate-400">{log.waktuPinjam}</p>
                               </div>
                             </div>
                             <div className="text-right text-[10px] font-bold text-slate-400 uppercase">
                               {log.waktuKembali ? <span className="text-emerald-500">Dikembalikan pada {log.waktuKembali.split(' ')[0]}</span> : 'Masih Dipinjam'}
                             </div>
                           </div>

                           <div className="grid grid-cols-3 gap-3 pt-2 border-t border-slate-50">
                             <div>
                               <p className="text-[9px] font-bold text-slate-400 uppercase mb-1">Foto Pinjam</p>
                               <img src={log.fotoPinjam} className="w-full h-16 object-cover rounded-lg border border-slate-100" />
                             </div>
                             {log.fotoKembali && (
                               <div>
                                 <p className="text-[9px] font-bold text-slate-400 uppercase mb-1">Foto Kembali</p>
                                 <img src={log.fotoKembali} className="w-full h-16 object-cover rounded-lg border border-slate-100" />
                               </div>
                             )}
                             <div>
                               <p className="text-[9px] font-bold text-slate-400 uppercase mb-1">Tanda Tangan</p>
                               <img src={log.ttdPinjam} className="w-full h-16 object-contain bg-slate-50 rounded-lg border border-slate-100 p-1" />
                             </div>
                           </div>
                         </div>
                       ))
                     )}
                   </div>
                </div>
              ) : (
                <>
                  {modalType === 'pinjam' && (
                    <div className="space-y-4">
                      <label className="block">
                        <span className="text-sm font-bold text-slate-700 ml-1">Nama Peminjam</span>
                        <input
                          type="text"
                          placeholder="Masukkan nama lengkap..."
                          className="mt-1 w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                          value={peminjamName}
                          onChange={(e) => setPeminjamName(e.target.value)}
                        />
                      </label>
                    </div>
                  )}

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Camera Simulation Area */}
                    <div className="space-y-2">
                      <span className="text-sm font-bold text-slate-700 ml-1">Foto {modalType === 'pinjam' ? 'Peminjam' : 'Barang Kembali'}</span>
                      <div className="aspect-video bg-slate-100 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center relative overflow-hidden group">
                        {capturedPhoto ? (
                          <>
                            <img src={capturedPhoto} className="w-full h-full object-cover" />
                            <button
                              onClick={() => setCapturedPhoto(null)}
                              className="absolute top-2 right-2 p-1 bg-white/80 rounded-full text-red-500 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                              <X className="w-4 h-4" />
                            </button>
                          </>
                        ) : (
                          <button
                            onClick={simulateCapture}
                            className="flex flex-col items-center gap-2 text-slate-400 hover:text-indigo-500 transition-colors"
                          >
                            <Camera className="w-10 h-10" />
                            <span className="text-xs font-bold uppercase">Ambil Foto</span>
                          </button>
                        )}
                      </div>
                    </div>

                    {/* Signature Area */}
                    {modalType === 'pinjam' && (
                      <div className="space-y-2">
                        <div className="flex justify-between items-end ml-1">
                          <span className="text-sm font-bold text-slate-700">Tanda Tangan Digital</span>
                          <button onClick={clearSignature} className="text-[10px] font-bold text-indigo-600 uppercase hover:underline">Hapus</button>
                        </div>
                        <div className="aspect-video bg-slate-50 rounded-2xl border border-slate-200 overflow-hidden touch-none">
                          <canvas
                            ref={canvasRef}
                            width={300}
                            height={150}
                            onMouseDown={startDrawing}
                            onMouseMove={draw}
                            onMouseUp={stopDrawing}
                            onMouseOut={stopDrawing}
                            onTouchStart={startDrawing}
                            onTouchMove={draw}
                            onTouchEnd={stopDrawing}
                            className="w-full h-full cursor-crosshair"
                          />
                        </div>
                      </div>
                    )}
                  </div>

                  <div className="bg-amber-50 p-4 rounded-2xl border border-amber-100 flex items-start gap-3">
                    <AlertCircle className="w-5 h-5 text-amber-600 shrink-0" />
                    <p className="text-xs text-amber-800 leading-relaxed">
                      Dengan menekan tombol simpan, peminjam menyatakan bertanggung jawab penuh atas barang yang dipinjam dalam kondisi baik.
                    </p>
                  </div>
                </>
              )}
            </div>

            <div className="p-6 bg-slate-50 border-t flex gap-3">
              <button
                onClick={() => setIsModalOpen(false)}
                className="flex-1 bg-white border border-slate-200 text-slate-600 py-3 rounded-2xl font-bold text-sm hover:bg-slate-50 transition-colors"
              >
                Batal
              </button>
              {modalType !== 'detail' && (
                <button
                  onClick={submitTransaction}
                  className="flex-[2] bg-indigo-600 text-white py-3 rounded-2xl font-bold text-sm hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200"
                >
                  Simpan Transaksi
                </button>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
