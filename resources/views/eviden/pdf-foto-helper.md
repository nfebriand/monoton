Di eviden/pdf.blade.php, baris foto path:
GANTI:
  $fotoPath = storage_path('app/public/' . $foto->path);
MENJADI:
  $fotoPath = public_path('uploads/' . $foto->path);
