@extends('layouts.app')
@section('title', $pemancar->nama_stasiun)
@section('page-title','Detail Pemancar')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('pemancar.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <span class="fw-bold text-truncate">{{ $pemancar->nama_stasiun }}</span>
    @if(auth()->user()->isAdmin())
    <div class="d-flex gap-2 ms-auto">
        <a href="{{ route('pemancar.edit',$pemancar) }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <form action="{{ route('pemancar.destroy',$pemancar) }}" method="POST"
              onsubmit="return confirm('Hapus pemancar ini beserta seluruh data terkait?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
        </form>
    </div>
    @endif
</div>

<div class="row g-3">
    {{-- KIRI: Info, Statistik, Foto, Riwayat --}}
    <div class="col-12 col-lg-8">

        {{-- Info Utama --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-broadcast-pin me-2 text-primary"></i>Informasi Pemancar</span>
                <span class="badge {{ $pemancar->is_active?'bg-success':'bg-secondary' }}">
                    {{ $pemancar->is_active?'Aktif':'Non-aktif' }}
                </span>
            </div>
            <div class="card-body">
                <h5 class="fw-bold mb-1">{{ $pemancar->nama_stasiun }}</h5>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <span class="badge {{ $pemancar->modulasi==='FM'?'bg-primary':'bg-warning text-dark' }}">
                        {{ $pemancar->modulasi }}
                        @if($pemancar->frekuensi) {{ $pemancar->frekuensi }} @endif
                    </span>
                    @if($pemancar->lokasi)
                    <span class="badge bg-secondary"><i class="bi bi-geo-alt me-1"></i>{{ $pemancar->lokasi }}</span>
                    @endif
                    @if($umurUnit !== null)
                    <span class="badge" style="background:#7b1fa2">
                        <i class="bi bi-clock-history me-1"></i>{{ $umurUnit }} tahun
                    </span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Merk</div>
                        <div class="fw-bold">{{ $pemancar->merk ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Tipe Unit</div>
                        <div class="fw-bold">{{ $pemancar->tipe_unit ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Tahun Pembuatan</div>
                        <div class="fw-bold mono">{{ $pemancar->tahun_pembuatan ?? '–' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">ID Pemancar</div>
                        <div class="fw-bold mono">#{{ str_pad($pemancar->id,4,'0',STR_PAD_LEFT) }}</div>
                    </div>
                </div>

                <hr style="border-color:var(--border)">

                <div class="text-muted mb-2" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">
                    Kapasitas Output Maksimum
                </div>
                <div class="row g-2">
                    <div class="col-4">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.65rem">FINAL PA</div>
                            <div class="fw-bold mono" style="font-size:1.1rem;color:var(--primary)">
                                {{ number_format($pemancar->kapasitas_output_final,0) }}<small style="font-size:.6rem"> W</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.65rem">DRIVER</div>
                            <div class="fw-bold mono" style="font-size:1.1rem">
                                {{ $pemancar->kapasitas_output_driver ? number_format($pemancar->kapasitas_output_driver,0) : '–' }}
                                @if($pemancar->kapasitas_output_driver)<small style="font-size:.6rem"> W</small>@endif
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.65rem">EXCITER</div>
                            <div class="fw-bold mono" style="font-size:1.1rem">
                                {{ $pemancar->kapasitas_output_exciter ? number_format($pemancar->kapasitas_output_exciter,0) : '–' }}
                                @if($pemancar->kapasitas_output_exciter)<small style="font-size:.6rem"> W</small>@endif
                            </div>
                        </div>
                    </div>
                </div>
                @if($efisiensi)
                <div class="text-muted mt-2" style="font-size:.72rem">
                    <i class="bi bi-graph-up me-1"></i>Rasio penguatan Driver→Final: <strong>{{ $efisiensi }}%</strong>
                </div>
                @endif

                @if($pemancar->keterangan)
                <div class="mt-3 p-3 rounded" style="background:var(--bg);border:1px solid var(--border);
                     border-left:4px solid var(--primary);font-size:.85rem;line-height:1.6;white-space:pre-line">
                    {{ $pemancar->keterangan }}
                </div>
                @endif
            </div>
        </div>

        {{-- ── Sistem & Instalasi (antena, kabel, konektor, audio) ── --}}
        @php
            $sis = $pemancar->sistem;
            $fmtN = fn($v, $d = 2) => \App\Models\PemancarSistem::num($v, $d);
            $baris = [];
            if ($sis) {
                $add = function (string $grup, string $label, $val) use (&$baris) {
                    if ($val !== null && $val !== '') $baris[$grup][] = [$label, $val];
                };
                $add('Antena', 'Merk', $sis->antena_merk);
                $add('Antena', 'Jenis', $sis->antena_jenis);
                $add('Antena', 'Jumlah Bay', $sis->antena_bay);
                $add('Antena', 'Daya Maks.', $sis->antena_max_power !== null ? $fmtN($sis->antena_max_power).' W' : null);
                $add('Antena', 'Gain', $sis->antena_gain !== null ? $fmtN($sis->antena_gain).' dBd' : null);
                $add('Antena', 'Tinggi', $sis->antena_tinggi !== null ? $fmtN($sis->antena_tinggi).' m' : null);
                $add('Kabel & Konektor', 'Kabel pemancar → antena', implode(' · ', array_filter([$sis->kabel_jenis, $sis->kabel_panjang !== null ? $fmtN($sis->kabel_panjang).' m' : null])));
                $add('Kabel & Konektor', 'Konektor Exciter → Driver', $sis->konektor_exciter_driver);
                $add('Kabel & Konektor', 'Konektor Driver → Final', $sis->konektor_driver_final);
                $add('Kabel & Konektor', 'Konektor Final → Antena', $sis->konektor_final_antena);
                if ($sis->ap_terpasang === false) {
                    $add('Audio Processor', 'Status', 'Tidak terpasang');
                } else {
                    $add('Audio Processor', 'Status', $sis->ap_terpasang === true ? 'Terpasang' : null);
                    $add('Audio Processor', 'Merk / Tipe', trim(($sis->ap_merk ?? '').' '.($sis->ap_tipe ?? '')));
                    $add('Audio Processor', 'Spesifikasi', $sis->ap_spek);
                }
            }
            // Sumber audio (bisa lebih dari satu)
            foreach ($pemancar->audioSumber as $as) {
                $judulAs = 'Sumber Audio'.($as->peran ? ' — '.$as->peran : '');
                $pf = function ($l, $v) use (&$baris, $judulAs) {
                    if ($v !== null && $v !== '') $baris[$judulAs][] = [$l, $v];
                };
                $pf('Sumber', $as->sumber ? (\App\Models\PemancarSistem::AUDIO_SUMBER[$as->sumber] ?? $as->sumber) : null);
                $pf('Konektor', $as->konektor);
                $pf('Perangkat', trim(($as->merk ?? '').' '.($as->tipe ?? '')));
                $pf('Frekuensi kerja STL', $as->stl_frekuensi !== null ? $fmtN($as->stl_frekuensi, 3).' MHz' : null);
                $pf('Catatan', $as->catatan);
            }
        @endphp
        @if(!empty($baris) || ($sis && $sis->catatan))
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-diagram-3 me-2 text-primary"></i>Sistem &amp; Instalasi</div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($baris as $grup => $items)
                    <div class="col-12 col-md-6">
                        <div class="fw-bold mb-1" style="font-size:.78rem;color:var(--primary)">{{ $grup }}</div>
                        @foreach($items as [$lbl, $val])
                        <div class="d-flex gap-2 py-1 border-bottom" style="font-size:.8rem">
                            <span class="text-muted" style="min-width:150px">{{ $lbl }}</span>
                            <span class="fw-semibold" style="white-space:pre-line">{{ $val }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
                @if($sis && $sis->catatan)
                <div class="mt-3 p-2 rounded" style="background:var(--bg);border:1px solid var(--border);font-size:.8rem;white-space:pre-line">{{ $sis->catatan }}</div>
                @endif
            </div>
        </div>
        @endif

        {{-- ── Diagram Koneksi (otomatis dari data) ── --}}
        @php
            $adaDiagram = $sis || $pemancar->audioSumber->isNotEmpty();
            $fmtW = fn($v) => \App\Models\PemancarSistem::num($v, 2);
        @endphp
        @if($adaDiagram)
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-share me-2 text-primary"></i>Diagram Koneksi <small class="text-muted fw-normal">(otomatis dari data di atas)</small></div>
            <div class="card-body">
                <div class="d-flex align-items-stretch flex-wrap gap-2" style="font-size:.76rem">
                    @if($pemancar->audioSumber->isNotEmpty())
                    <div class="d-flex flex-column gap-1 justify-content-center">
                        @foreach($pemancar->audioSumber as $as)
                        <div class="p-2 rounded text-center" style="background:#e8f4fd;border:1px solid #b6dcf5;min-width:120px">
                            <div class="fw-bold">{{ $as->sumber ?: 'Audio' }}{{ $as->peran ? ' ('.$as->peran.')' : '' }}</div>
                            @if($as->merk || $as->tipe)<div>{{ trim($as->merk.' '.$as->tipe) }}</div>@endif
                            @if($as->stl_frekuensi !== null)<div class="mono">{{ \App\Models\PemancarSistem::num($as->stl_frekuensi, 3) }} MHz</div>@endif
                        </div>
                        @endforeach
                    </div>
                    <div class="d-flex flex-column justify-content-center text-center text-muted" style="min-width:60px">
                        <i class="bi bi-arrow-right fs-5"></i>
                        <small>{{ $pemancar->audioSumber->pluck('konektor')->filter()->unique()->implode(' / ') ?: '—' }}</small>
                    </div>
                    @endif

                    @php
                        $rantai = [
                            ['Exciter', $pemancar->kapasitas_output_exciter ? $fmtW($pemancar->kapasitas_output_exciter).' W' : null, $sis->konektor_exciter_driver ?? null],
                            ['Driver',  $pemancar->kapasitas_output_driver  ? $fmtW($pemancar->kapasitas_output_driver).' W'  : null, $sis->konektor_driver_final ?? null],
                            ['Final PA', $pemancar->kapasitas_output_final ? $fmtW($pemancar->kapasitas_output_final).' W' : null, $sis->konektor_final_antena ?? null],
                        ];
                    @endphp
                    @foreach($rantai as [$nm, $w, $kon])
                    <div class="p-2 rounded text-center d-flex flex-column justify-content-center" style="background:#fff3e0;border:1px solid #ffd699;min-width:90px">
                        <div class="fw-bold">{{ $nm }}</div>
                        @if($w)<div class="mono">{{ $w }}</div>@endif
                    </div>
                    <div class="d-flex flex-column justify-content-center text-center text-muted" style="min-width:60px">
                        <i class="bi bi-arrow-right fs-5"></i>
                        <small>{{ $kon ?: '—' }}</small>
                    </div>
                    @endforeach

                    @if($sis && ($sis->kabel_jenis || $sis->kabel_panjang !== null))
                    <div class="p-2 rounded text-center d-flex flex-column justify-content-center" style="background:#f3e5f5;border:1px solid #d9b3e6;min-width:100px">
                        <div class="fw-bold">Kabel</div>
                        <div>{{ $sis->kabel_jenis }}</div>
                        @if($sis->kabel_panjang !== null)<div class="mono">{{ \App\Models\PemancarSistem::num($sis->kabel_panjang) }} m</div>@endif
                    </div>
                    <div class="d-flex flex-column justify-content-center text-muted" style="min-width:30px"><i class="bi bi-arrow-right fs-5"></i></div>
                    @endif

                    <div class="p-2 rounded text-center d-flex flex-column justify-content-center" style="background:#e8f5e9;border:1px solid #a5d6a7;min-width:110px">
                        <div class="fw-bold"><i class="bi bi-broadcast-pin me-1"></i>Antena</div>
                        @if($sis)
                        @if($sis->antena_merk || $sis->antena_jenis)<div>{{ trim($sis->antena_merk.' '.$sis->antena_jenis) }}</div>@endif
                        @if($sis->antena_bay)<div>{{ $sis->antena_bay }} bay</div>@endif
                        @if($sis->antena_gain !== null)<div class="mono">{{ \App\Models\PemancarSistem::num($sis->antena_gain) }} dBd</div>@endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Dokumen & Manual ── --}}
        @php $dokGroup = $pemancar->dokumen->groupBy('kategori'); @endphp
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-journal-bookmark me-2 text-primary"></i>Dokumen &amp; Manual</span>
                <span class="badge bg-secondary">{{ $pemancar->dokumen->count() }} berkas</span>
            </div>
            <div class="card-body">
                @forelse(\App\Models\PemancarDokumen::KATEGORI as $kk => $kl)
                    @continue(!isset($dokGroup[$kk]))
                    <div class="fw-bold mb-1 mt-2" style="font-size:.78rem;color:var(--primary)">{{ $kl }}</div>
                    @if($kk === 'skematik_koneksi')
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @foreach($dokGroup[$kk] as $d)
                            @if($d->is_image)
                            <a href="{{ route('pemancar.dokumen.show', [$pemancar, $d]) }}" target="_blank" title="{{ $d->judul }}">
                                <img src="{{ route('pemancar.dokumen.show', [$pemancar, $d]) }}" alt="{{ $d->judul }}"
                                     style="max-width:100%;max-height:260px;border:1px solid var(--border);border-radius:6px">
                            </a>
                            @endif
                        @endforeach
                    </div>
                    @endif
                    @foreach($dokGroup[$kk] as $d)
                    <div class="d-flex align-items-center gap-2 py-1 border-bottom" style="font-size:.8rem">
                        <i class="bi {{ $d->is_pdf ? 'bi-file-earmark-pdf text-danger' : ($d->is_image ? 'bi-file-earmark-image text-success' : 'bi-file-earmark text-secondary') }} fs-5"></i>
                        <div class="flex-fill text-truncate">
                            <div class="fw-semibold text-truncate">{{ $d->judul }}</div>
                            <div class="text-muted" style="font-size:.68rem">{{ $d->nama_asli }} · {{ $d->ukuran_label }} · {{ $d->created_at?->format('d/m/Y') }}</div>
                        </div>
                        @if($d->is_pdf || $d->is_image)
                        <a href="{{ route('pemancar.dokumen.show', [$pemancar, $d]) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Buka"><i class="bi bi-eye"></i></a>
                        @endif
                        <a href="{{ route('pemancar.dokumen.show', [$pemancar, $d, 'unduh' => 1]) }}" class="btn btn-sm btn-outline-secondary" title="Unduh"><i class="bi bi-download"></i></a>
                        @if(auth()->user()->isAdmin())
                        <form action="{{ route('pemancar.dokumen.destroy', [$pemancar, $d]) }}" method="POST" onsubmit="return confirm('Hapus berkas ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                @empty
                @endforelse
                @if($pemancar->dokumen->isEmpty())
                <div class="text-muted text-center py-3" style="font-size:.8rem"><i class="bi bi-folder2-open d-block fs-3 mb-1"></i>Belum ada dokumen.</div>
                @endif

                @if(auth()->user()->isAdmin())
                <hr>
                <form action="{{ route('pemancar.dokumen.store', $pemancar) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" class="form-select form-select-sm" required>
                                @foreach(\App\Models\PemancarDokumen::KATEGORI as $kk => $kl)
                                <option value="{{ $kk }}">{{ $kl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label">Judul <small class="text-muted">(opsional, untuk 1 berkas)</small></label>
                            <input type="text" name="judul" class="form-control form-control-sm" maxlength="150" placeholder="mis. Manual Book Exciter XYZ">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Berkas <small class="text-muted">(PDF, JPG, PNG, WEBP, DOC/DOCX, XLS/XLSX, ZIP · maks. 50 MB per berkas · bisa pilih banyak)</small></label>
                            <input type="file" name="berkas[]" class="form-control form-control-sm" multiple required
                                   accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.zip">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-sm btn-primary"><i class="bi bi-upload me-1"></i>Unggah</button>
                        </div>
                    </div>
                </form>
                @endif
            </div>
        </div>

        {{-- ── Statistik 30 Hari ── --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-bar-chart-line me-2 text-success"></i>Statistik 30 Hari Terakhir</span>
                <span class="badge bg-secondary">{{ $statistik['total_log'] }} pencatatan</span>
            </div>
            <div class="card-body">
                @if($statistik['total_log'] > 0)
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.62rem">RATA OUTPUT FINAL</div>
                            <div class="fw-bold mono" style="font-size:1rem">
                                {{ $statistik['rata_output'] ? number_format($statistik['rata_output'],1).' W' : '–' }}
                            </div>
                            @if($statistik['min_output'] && $statistik['max_output'])
                            <div class="text-muted" style="font-size:.6rem">
                                {{ number_format($statistik['min_output'],0) }} – {{ number_format($statistik['max_output'],0) }} W
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        @php
                            $rv = $statistik['rata_vswr'];
                            $vc = $rv ? ($rv<=1.5?'vswr-baik':($rv<=2?'vswr-sedang':'vswr-buruk')) : '';
                        @endphp
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.62rem">RATA VSWR FINAL</div>
                            <div class="fw-bold mono {{ $vc }}" style="font-size:1rem">
                                {{ $rv ? number_format($rv,3) : '–' }}
                            </div>
                            @if($statistik['max_vswr'])
                            <div class="text-muted" style="font-size:.6rem">max: {{ number_format($statistik['max_vswr'],3) }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.62rem">RATA SUHU PEMANCAR</div>
                            <div class="fw-bold mono" style="font-size:1rem">
                                {{ $statistik['rata_suhu_pmcr'] ? number_format($statistik['rata_suhu_pmcr'],1).'°C' : '–' }}
                            </div>
                            @if($statistik['max_suhu_pmcr'])
                            <div class="text-muted" style="font-size:.6rem">max: {{ number_format($statistik['max_suhu_pmcr'],1) }}°C</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded text-center" style="background:var(--bg);border:1px solid var(--border)">
                            <div class="text-muted" style="font-size:.62rem">VSWR BERMASALAH</div>
                            <div class="fw-bold mono" style="font-size:1rem;color:{{ $statistik['jumlah_vswr_buruk']>0?'var(--danger)':'var(--success)' }}">
                                {{ $statistik['jumlah_vswr_buruk'] }}x
                            </div>
                            <div class="text-muted" style="font-size:.6rem">VSWR ≥ 2.0</div>
                        </div>
                    </div>
                </div>
                @if($statistik['rata_suhu_ruang'])
                <div class="text-muted mt-2" style="font-size:.72rem">
                    <i class="bi bi-thermometer-half me-1"></i>Rata-rata suhu ruangan: <strong>{{ number_format($statistik['rata_suhu_ruang'],1) }}°C</strong>
                </div>
                @endif
                @else
                <div class="text-center text-muted py-3 small">
                    <i class="bi bi-bar-chart d-block fs-3 mb-1"></i>Belum ada data 30 hari terakhir
                </div>
                @endif
            </div>
        </div>

        {{-- Foto --}}
        @if($pemancar->fotos->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-images me-2"></i>Foto Pemancar ({{ $pemancar->fotos->count() }})</div>
            <div class="card-body">
                <div style="height:280px;border-radius:8px;overflow:hidden;margin-bottom:.75rem;background:var(--bg)">
                    <img id="pemancarFotoUtama" src="{{ $pemancar->fotos->first()->url }}"
                         onerror="this.parentElement.style.background='#eee'"
                         onclick="bukaLightbox(this.src)"
                         style="width:100%;height:100%;object-fit:cover;cursor:zoom-in;display:block">
                </div>
                @if($pemancar->fotos->count()>1)
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:.5rem">
                    @foreach($pemancar->fotos as $i=>$foto)
                    <div style="aspect-ratio:1;border-radius:6px;overflow:hidden;cursor:pointer;
                         border:2px solid {{ $i===0?'var(--primary)':'var(--border)' }}"
                         onclick="document.getElementById('pemancarFotoUtama').src='{{ $foto->url }}'">
                        <img src="{{ $foto->thumb_url }}" style="width:100%;height:100%;object-fit:cover"
                             onerror="this.parentElement.style.background='#eee'">
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Riwayat 10 Log Terakhir --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-journal-text me-2 text-primary"></i>10 Pencatatan Terakhir</span>
                <a href="{{ route('operasional.index') }}?pemancar_id={{ $pemancar->id }}" class="btn btn-sm btn-outline-primary" style="font-size:.7rem">
                    Lihat Semua
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Waktu</th><th>Operator</th>
                            <th>Out Final</th><th>VSWR</th><th>Suhu Pmcr</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayatLog as $log)
                        @php
                            $v=$log->vswr_final;
                            $vc=$v ? ($v<=1.5?'vswr-baik':($v<=2?'vswr-sedang':'vswr-buruk')) : '';
                        @endphp
                        <tr style="cursor:pointer" onclick="window.location='{{ route('operasional.show',$log) }}'">
                            <td class="ps-3 mono" style="font-size:.76rem">{{ $log->dicatat_pada->format('d/m/y H:i') }}</td>
                            <td style="font-size:.78rem">{{ $log->user->name }}</td>
                            <td class="mono" style="font-size:.78rem">{{ $log->output_final_pa ? number_format($log->output_final_pa,1).'W' : '–' }}</td>
                            <td class="mono {{ $vc }}" style="font-size:.78rem">{{ $v ? number_format($v,3) : '–' }}</td>
                            <td class="mono" style="font-size:.78rem">{{ $log->suhu_pemancar!==null ? $log->suhu_pemancar.'°C' : '–' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- KANAN: Operator, Log Terakhir, Riwayat --}}
    <div class="col-12 col-lg-4">

        {{-- Operator yang bertugas di lokasi ini --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-people me-2 text-primary"></i>Operator Bertugas</span>
                @if($pemancar->lokasi)
                <span class="badge bg-secondary" style="font-size:.62rem">{{ $pemancar->lokasi }}</span>
                @endif
            </div>
            <div class="card-body p-0">
                @if($operatorLokasi->isNotEmpty())
                @foreach($operatorLokasi as $op)
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="border-color:var(--border)!important">
                    <div style="width:34px;height:34px;min-width:34px;border-radius:50%;
                         background:var(--primary);color:#fff;display:flex;align-items:center;
                         justify-content:center;font-size:.72rem;font-weight:700">
                        {{ strtoupper(substr($op->name,0,2)) }}
                    </div>
                    <div class="flex-fill" style="min-width:0">
                        <div style="font-size:.84rem;font-weight:600">{{ $op->name }}</div>
                        <div class="text-muted" style="font-size:.68rem">
                            {{ $op->nip ? 'NIP. '.$op->nip : 'Operator' }}
                        </div>
                    </div>
                    <span class="badge {{ $op->is_active?'bg-success':'bg-secondary' }}" style="font-size:.6rem">
                        {{ $op->is_active?'Aktif':'Off' }}
                    </span>
                </div>
                @endforeach
                @else
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-person-x d-block fs-3 mb-1"></i>
                    @if($pemancar->lokasi)
                    Belum ada operator terdaftar<br>di lokasi {{ $pemancar->lokasi }}
                    @else
                    Lokasi pemancar belum diset
                    @endif
                </div>
                @endif
            </div>
            @if($operatorLokasi->isNotEmpty())
            <div class="card-footer py-2 text-center" style="border-color:var(--border)">
                <small class="text-muted">{{ $operatorLokasi->count() }} operator terdaftar</small>
            </div>
            @endif
        </div>

        {{-- Log Terakhir --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clock-history me-2 text-success"></i>Pencatatan Terakhir</div>
            <div class="card-body">
                @if($logTerakhir)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Waktu</span>
                    <span class="mono fw-bold" style="font-size:.78rem">{{ $logTerakhir->dicatat_pada->format('d/m/Y H:i') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Operator</span>
                    <span style="font-size:.8rem;font-weight:600">{{ $logTerakhir->user->name }}</span>
                </div>
                @if($logTerakhir->jadwalShift)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Shift</span>
                    <span style="font-size:.8rem">{{ $logTerakhir->jadwalShift->shift_label }}</span>
                </div>
                @endif
                @if($logTerakhir->output_final_pa)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Output Final</span>
                    <span class="mono" style="font-size:.78rem">{{ number_format($logTerakhir->output_final_pa,1) }} W</span>
                </div>
                @endif
                @if($logTerakhir->vswr_final)
                @php
                    $v=$logTerakhir->vswr_final;
                    $cls=$v<=1.5?'vswr-baik':($v<=2?'vswr-sedang':'vswr-buruk');
                @endphp
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">VSWR Final</span>
                    <span class="mono {{ $cls }}" style="font-size:.78rem">{{ number_format($v,3) }}</span>
                </div>
                @endif
                @if($logTerakhir->keterangan)
                <div class="mt-2 p-2 rounded" style="background:var(--bg);font-size:.75rem;line-height:1.5">
                    {{ $logTerakhir->keterangan }}
                </div>
                @endif
                <a href="{{ route('operasional.show',$logTerakhir) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">
                    <i class="bi bi-eye me-1"></i>Lihat Detail Log
                </a>
                @else
                <div class="text-center text-muted py-3 small">
                    <i class="bi bi-journal-x d-block fs-3 mb-1"></i>
                    Belum ada pencatatan
                </div>
                @endif
            </div>
        </div>

        {{-- Riwayat Pencatatan Sepanjang Waktu --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-archive me-2 text-warning"></i>Riwayat Sepanjang Waktu</div>
            <div class="card-body p-0">
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom" style="border-color:var(--border)!important">
                    <span class="text-muted" style="font-size:.78rem">Total Pencatatan</span>
                    <span class="mono fw-bold">{{ number_format($totalLogSemua) }}</span>
                </div>
                @if($logPertama)
                <div class="d-flex align-items-center justify-content-between px-3 py-2">
                    <span class="text-muted" style="font-size:.78rem">Pencatatan Pertama</span>
                    <span class="mono" style="font-size:.75rem">{{ $logPertama->dicatat_pada->format('d/m/Y') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
