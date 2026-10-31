@php
    $logoB64 = '';
    $logoRel = \App\Models\AppSetting::logoInstansiPath();
    if ($logoRel && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoRel)) {
        $ext = strtolower(pathinfo($logoRel, PATHINFO_EXTENSION));
        $logoB64 = 'data:'.($ext === 'png' ? 'image/png' : 'image/jpeg').';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($logoRel));
    }
@endphp
<table class="hd"><tr>
    <td class="logo">@if($logoB64)<img src="{{ $logoB64 }}" style="max-width:60px;max-height:60px">@endif</td>
    <td><h3>{{ $judul }}</h3>@if(!empty($sub))<p>{{ $sub }}</p>@endif<p>{{ $settings['satuan_kerja'] ?? 'LPP RRI Bandarlampung' }}</p></td>
</tr></table>
