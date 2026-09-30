@extends('layouts.app')
@section('title','Ganti Password')
@section('page-title','Ganti Password')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-md-6 col-lg-5">

<div class="card">
    <div class="card-header">
        <i class="bi bi-key me-2 text-warning"></i>Ganti Password
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded" style="background:#f8fafc;border:1px solid #eee">
            <div style="width:44px;height:44px;min-width:44px;border-radius:50%;background:var(--primary);
                 color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.95rem">
                {{ strtoupper(substr(auth()->user()->name,0,2)) }}
            </div>
            <div>
                <div class="fw-bold" style="font-size:.9rem">{{ auth()->user()->name }}</div>
                <div class="text-muted" style="font-size:.75rem">{{ auth()->user()->email }}</div>
                <div style="font-size:.7rem;color:var(--primary)">
                    {{ auth()->user()->isAdmin()?'Administrator':'Operator' }}
                    @if(auth()->user()->lokasi_dinas) — {{ auth()->user()->lokasi_dinas }}@endif
                </div>
            </div>
        </div>

        <form action="{{ route('users.update-password') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Password Saat Ini <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="password" name="current_password" id="pw_current"
                       class="form-control @error('current_password') is-invalid @enderror"
                       required autocomplete="current-password">
                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('pw_current')">
                    <i class="bi bi-eye" id="ic_current"></i>
                </button>
                @error('current_password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Password Baru <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="password" name="new_password" id="pw_new"
                       class="form-control @error('new_password') is-invalid @enderror"
                       required minlength="8" autocomplete="new-password"
                       oninput="checkStrength(this.value)">
                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('pw_new')">
                    <i class="bi bi-eye" id="ic_new"></i>
                </button>
                @error('new_password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            {{-- Strength indicator --}}
            <div class="mt-2">
                <div style="height:4px;border-radius:2px;background:#eee;overflow:hidden">
                    <div id="strength-bar" style="height:100%;width:0%;transition:all .3s;border-radius:2px"></div>
                </div>
                <div id="strength-label" class="text-muted mt-1" style="font-size:.7rem"></div>
            </div>
            <div class="form-text">Minimal 8 karakter</div>
        </div>

        <div class="mb-4">
            <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="password" name="new_password_confirmation" id="pw_confirm"
                       class="form-control" required autocomplete="new-password"
                       oninput="checkMatch()">
                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('pw_confirm')">
                    <i class="bi bi-eye" id="ic_confirm"></i>
                </button>
            </div>
            <div id="match-label" class="mt-1" style="font-size:.72rem"></div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-custom flex-fill">
                <i class="bi bi-save me-1"></i>Simpan Password Baru
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection

@push('scripts')
<script>
function togglePw(id){
    const inp = document.getElementById(id);
    const ic  = document.getElementById('ic_'+id.replace('pw_',''));
    const show = inp.type==='password';
    inp.type = show?'text':'password';
    ic.className = show?'bi bi-eye-slash':'bi bi-eye';
}

function checkStrength(val){
    const bar   = document.getElementById('strength-bar');
    const label = document.getElementById('strength-label');
    let score = 0;
    if(val.length >= 8)  score++;
    if(val.length >= 12) score++;
    if(/[A-Z]/.test(val)) score++;
    if(/[0-9]/.test(val)) score++;
    if(/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        {w:'0%',    color:'#eee',      text:''},
        {w:'25%',   color:'#ee5a24',   text:'⚠ Lemah'},
        {w:'50%',   color:'#ff9f43',   text:'○ Sedang'},
        {w:'75%',   color:'#00b894',   text:'● Kuat'},
        {w:'100%',  color:'#10ac84',   text:'✅ Sangat Kuat'},
    ];
    const lv = levels[Math.min(score,4)];
    bar.style.width   = lv.w;
    bar.style.background = lv.color;
    label.textContent = lv.text;
    label.style.color = lv.color;
}

function checkMatch(){
    const nw  = document.getElementById('pw_new').value;
    const cf  = document.getElementById('pw_confirm').value;
    const lbl = document.getElementById('match-label');
    if(!cf) { lbl.textContent=''; return; }
    if(nw===cf){
        lbl.textContent='✅ Password cocok'; lbl.style.color='#10ac84';
    } else {
        lbl.textContent='❌ Password tidak cocok'; lbl.style.color='#ee5a24';
    }
}
</script>
@endpush
