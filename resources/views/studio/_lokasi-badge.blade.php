@if(!empty($lokasi))
<span class="badge" style="font-size:.65rem;background:{{ \App\Support\StudioLokasi::color($lokasi) }}">{{ \App\Support\StudioLokasi::label($lokasi) }}</span>
@endif
