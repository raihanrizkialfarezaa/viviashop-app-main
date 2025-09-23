# ViViaShop — Panduan Pelanggan Lengkap (bahasa sederhana, dokumentasi fitur & alur)

Versi: 2.0
Tanggal: 23 September 2025

Panduan ini dibuat untuk pengguna akhir (non-teknis) yang ingin memahami seluruh fitur yang tersedia di toko: tiap halaman, tindakan yang dapat dilakukan pengguna, bagaimana alur pembayaran bekerja, cara melanjutkan pesanan yang tertunda, alur print-service, serta skenario kegagalan dan cara menanganinya.

Dokumen ini dibuat se-lengkap mungkin: berisi daftar URL yang relevan, perilaku halaman, kondisi yang memicu tombol/aksi tertentu, dan tips untuk menghindari masalah.

Catatan: contoh URL di panduan ini menggunakan alamat pengembangan `http://127.0.0.1:8000`. Ganti url ini dengan domain produksi toko `https://viviashop.com/`.

Daftar isi panjang (klik bagian yang Anda butuhkan):

-   Bagian A — Pemahaman dasar (akun, cart, produk)
-   Bagian B — Daftar halaman & URL penting (halaman, kegunaan, tombol utama)
-   Bagian C — Alur Checkout (detil teknis yang relevan untuk pengguna)
-   Bagian D — Resume Checkout: kapan dan bagaimana
-   Bagian E — Pembayaran & notifikasi (Midtrans dan manual)
-   Bagian F — Print-service / Smart Print: sesi, unggah, checkout, status
-   Bagian G — Barcode & label (user-facing behavior)
-   Bagian H — My Orders, Invoice, Resi & tindakan yang tersedia
-   Bagian I — Kesalahan umum & troubleshooting (langkah-langkah pemeriksaan)
-   Bagian J — Contoh pesan ke support (template + data wajib)
-   Lampiran: Peta route/URL ke fitur (ringkasan teknis singkat untuk admin)

---

## Bagian A — Pemahaman dasar

1. Akun & alasan membuatnya

-   Akun menyimpan alamat, memudahkan checkout, menyimpan riwayat pesanan, dan memungkinkan Anda melanjutkan pesanan yang belum selesai.

2. Keranjang (cart)

-   Keranjang disimpan di sesi browser. Jika Anda berganti browser/perangkat tanpa login, isi keranjang tidak otomatis berpindah.

3. Tipe produk yang sering ditemui

-   Simple product: satu produk satu SKU.
-   Configurable product: produk induk dengan varian (warna/ukuran/fitur). Anda wajib memilih varian sebelum menambahkan ke keranjang.

4. Print-service (opsional)

-   Layanan cetak terpisah: Anda membuat sesi cetak, unggah file, pilih varian print, lalu checkout mirip order biasa. Sesi cetak memiliki token unik (link) yang bisa disimpan untuk melanjutkan nanti.

---

## Bagian B — Daftar halaman & URL penting (apa yang dilakukan tiap halaman)

Berikut halaman yang paling sering Anda gunakan. Saya sertakan URL, tombol utama, dan perilaku yang akan Anda lihat.

1. Halaman utama — / (Homepage)

-   Kegunaan: browse kategori, cari produk.
-   Tombol penting: produk -> klik judul/foto untuk melihat detail.

2. Produk detail — /product/{slug}

-   Kegunaan: melihat deskripsi, memilih varian (jika ada), menambah jumlah.
-   Tombol: "Add to Cart".

3. Keranjang — /carts

-   Kegunaan: ubah qty, hapus item, lanjut checkout.
-   Tombol: "Proceed to Checkout".

4. Checkout — /orders/checkout (HARUS login)

-   Kegunaan: isi alamat, pilih metode pengiriman & pembayaran, place order.
-   Tombol & aksi:
    -   Pilih delivery method: Self (ambil di toko) atau Courier.
    -   Jika Courier dipilih: pilih provinsi -> auto-load kota -> pilih kota -> auto-load kecamatan -> tampilkan opsi ongkir.
    -   Pilih payment method: manual / automatic (Midtrans) / cod / bayar di toko.
    -   Place Order: membuat pesanan (order) di sistem.

5. My Orders (daftar) — /orders

-   Kegunaan: lihat riwayat & status order Anda.
-   Tombol/aksi per baris order:
    -   Details (lihat detail order)
    -   Resume (hanya muncul jika order unpaid/waiting dan status bukan completed/cancelled)
    -   Confirm (hanya untuk metode manual: upload bukti transfer bila unpaid)

6. Order detail — /orders/{orderId}

-   Kegunaan: lihat detail, alamat, invoice, history status, upload bukti.

7. Invoice download — biasanya tautan dari halaman order detail (PDF)

8. Print-service entry (Smart Print) — /smart-print

-   Kegunaan: memulai sesi cetak (generate token & link ke /print-service/{token}).

9. Print-service session (customer) — /print-service/{token}

-   Kegunaan: unggah file, lihat file sesi, hitung biaya, checkout print order.

10. Print-service APIs (dipanggil oleh halaman, bukan langsung untuk pengguna):

-   /print-service/upload (unggah files)
-   /print-service/calculate (hitung harga)
-   /print-service/checkout (buat print order)

11. Barcode preview/print (admin-facing, tapi berguna bila toko menyediakan cetakan barcode)

-   Preview: /barcode/preview ; print: /barcode/print/landscape atau /barcode/print/portrait

12. Payment webhook (Midtrans) — POST /payments/notification

-   Ini endpoint internal untuk menerima notifikasi pembayaran, bukan untuk pengguna membuka di browser.

---

## Bagian C — Alur Checkout: detil langkah & apa yang terjadi

Berikut uraian langkah demi langkah beserta apa yang terjadi sistem (penjelasan tetap non-teknis):

Langkah 0: Persiapan

-   Pastikan Anda login dan keranjang berisi produk. Jika ada produk configurable, pastikan varian yang dipilih benar.

Langkah 1: Buka /orders/checkout

-   Form akan menampilkan:
    -   Nama, alamat (autofill dari profil bila tersedia)
    -   Pilihan delivery method (Self / Courier)
    -   Pilihan payment method (manual / automatic / cod / bayar di toko)
    -   Ringkasan pesanan (items, subtotal, shipping, total)

Langkah 2: Pilih courier (jika kirim)

-   Pilih provinsi -> sistem meminta daftar kota -> pilih kota -> sistem meminta daftar kecamatan -> pilih kecamatan -> sistem hitung opsi ongkir dan tampilkan di dropdown "Shipping".

Langkah 3: Pilih payment method

-   Automatic: sistem akan membuat token pembayaran (Midtrans) setelah Anda klik Place Order dan akan mengarahkan Anda ke halaman Midtrans.
-   Manual: Anda akan menerima instruksi bank transfer dan dapat mengupload bukti transfer di halaman order.

Langkah 4: Place Order

-   Ketika klik Place Order, sistem:
    1. Memvalidasi data alamat & telefone.
    2. Jika valid, membuat record order dan anak-record (order items & shipment).
    3. Jika payment_method automatic: mencoba menghasilkan token pembayaran & menyimpan token + redirect_url.
    4. Jika order dibuat sukses, Anda akan diarahkan ke halaman "Order Received" (/orders/received/{orderId}) atau langsung ke payment gateway jika automatic.

Apa yang harus Anda periksa jika gagal:

-   Jika tombol Place Order tidak bereaksi: pastikan tidak ada notifikasi error pada form (field required) dan Anda tidak menekan dua kali.
-   Jika muncul error "Cart is empty": pastikan Anda punya item di cart; untuk resume, sistem dapat menampilkan items dari order lama.

---

## Bagian D — Resume Checkout (detil lengkap)

Ketentuan resume:

-   Hanya order dengan payment_status `unpaid` atau `waiting` dan status bukan `completed`/`cancelled` yang boleh di-resume.
-   Hanya pemilik order (pemilik akun) yang bisa melihat tombol Resume.

Langkah melanjutkan:

1. Buka /orders (My Orders)
2. Temukan pesanan yang ingin dilanjutkan
3. Klik Resume — Anda akan diarahkan ke /orders/checkout?order_id={id}
4. Halaman checkout akan mengisi data items dari order lama (meskipun keranjang Anda kosong)
5. Ubah data jika perlu (alamat, shipping), lalu Place Order.

Catatan penting:

-   Resume digunakan untuk menyelesaikan pembayaran atau memperbaiki detail alamat/shipping. Sistem saat ini tidak otomatis menggabungkan perubahan besar pada item; jika Anda ingin menambah/menghapus item saat resume, disarankan buat order baru agar transaksi terkelola rapi.

Jika Resume tidak muncul:

-   Jika tombol Resume hilang, besar kemungkinan order sudah dibayar atau di-cancel. Cek status pada halaman detail order.

---

## Bagian E — Pembayaran & notifikasi (Midtrans dan manual)

1. Pembayaran Automatic (Midtrans)

-   Jalur: Pilih payment_method `automatic` di checkout.
-   Setelah Place Order: sistem mencoba membuat transaksi Midtrans (token + redirect url).
-   Anda dibawa ke halaman Midtrans; selesaikan pembayaran.
-   Midtrans mengirim notifikasi kembali ke sistem toko; jika sukses, status order berubah menjadi `paid` dan order umumnya berpindah ke `completed` atau `confirmed` tergantung metode pengiriman.

2. Pembayaran Manual (transfer bank)

-   Pilih manual -> sistem menampilkan instruksi transfer.
-   Lakukan transfer sesuai jumlah.
-   Kembali ke My Orders -> pilih order -> upload bukti -> submit.
-   Admin akan verifikasi bukti; jika valid, admin menandai order sebagai paid.

3. Skenario timeout / expired

-   Jika pelanggan tidak menyelesaikan pembayaran (mis. token kadaluarsa), Midtrans akan mengirim status `expire` -> order akan diberi payment_status `cancelled`.
-   Jika Anda rasa ada masalah (mis. Anda sudah transfer tetapi status belum berubah), hubungi support dengan bukti transfer.

4. Keamanan & privasi

-   Informasi pembayaran tidak disimpan secara raw di halaman pelanggan. Pastikan selalu gunakan koneksi yang aman (https) di produksi.

---

## Bagian F — Print-service / Smart Print (alur lengkap)

Layanan ini cukup lengkap; saya uraikan detil agar Anda tahu tiap langkah.

Alur ringkas:

1. Masuk /smart-print -> pilih paper product
2. Klik Generate Session -> sistem membuat sesi cetak dan memberikan token
3. Buka /print-service/{token}
4. Unggah file (bisa beberapa file) -> sistem memindai halaman dan menampilkan jumlah halaman
5. Pilih variant (print type: BW/Color, paper size, quantity)
6. Klik Calculate -> sistem menampilkan estimasi harga
7. Klik Checkout -> sistem membuat print order
8. Lanjut ke pembayaran (Midtrans/manual) seperti order biasa
9. Setelah pembayaran dikonfirmasi, admin akan memproses file untuk dicetak

Detail halaman /print-service/{token}:

-   Tombol & fitur:
    -   Upload file (batas ukuran per file biasanya ditentukan oleh pengaturan server)
    -   Preview file (download/lihat)
    -   Hapus file dari sesi sebelum checkout
    -   Get Session Files (lihat ringkasan pages total)
    -   Calculate price berdasarkan variant dan pages
    -   Checkout untuk membuat PrintOrder

Status yang akan Anda lihat di My Orders untuk print:

-   pending_upload: sesi dibuat, file belum lengkap
-   uploaded: file selesai diupload
-   payment_pending / unpaid: menunggu pembayaran
-   payment_confirmed: pembayaran terkonfirmasi
-   ready_to_print: admin sudah siap mencetak
-   printing -> printed -> completed

Catatan: simpan link sesi `/print-service/{token}` jika Anda belum menyelesaikan checkout; link ini memungkinkan Anda kembali ke sesi yang sama.

---

## Bagian G — Barcode & label (untuk pelanggan)

Perilaku pengguna:

-   Biasanya pelanggan tidak perlu membuat barcode; admin dapat membuat barcode untuk semua produk.
-   Jika Anda ingin mencetak barcode label untuk katalog, tanyakan admin; mereka dapat menggunakan fitur admin untuk generate barcode lalu menggunakan preview/print.

Jika Anda sebagai pelanggan ingin cetak label: gunakan Smart Print untuk unggah file label (atau minta admin men-generate barcode lalu unggah file label ke sesi Anda).

---

## Bagian H — My Orders, Invoice & tindakan

1. My Orders (/orders)

-   Menampilkan daftar order milik Anda.
-   Filter & sort di UI: ada kotak pencarian (q) dan pilihan sort/direction. (Catatan: jika belum bekerja, minta admin mengaktifkan filter server-side.)

2. Aksi yang mungkin muncul pada tiap order

-   Details: lihat halaman detail
-   Resume: muncul bila order unpaid/waiting dan status bukan completed/cancelled
-   Confirm: untuk upload bukti pembayaran (pada metode manual)

3. Invoice

-   Anda dapat mendownload invoice PDF dari halaman detail order. Gunakan fitur ini untuk bukti pembelian/keperluan akuntansi.

4. Resi pengiriman

-   Jika order dikirim, nomor resi akan tampil di detail. Anda bisa memantau status pengiriman di pihak kurir dengan nomor tersebut.

---

## Bagian I — Kesalahan umum & troubleshooting (langkah-langkah praktis)

Berikut daftar masalah yang sering terjadi beserta langkah pengecekan sebelum hubungi support.

1. "Cart is empty" saat mau checkout

-   Pastikan Anda menambahkan item ke cart.
-   Jika Anda resume order, sistem akan memuat item dari order lama meskipun cart kosong.

2. Ongkir tidak muncul

-   Pastikan Anda sudah memilih provinsi > kota > kecamatan.

3. Tombol Resume tidak tampil

-   Cek status order: jika sudah paid/completed/cancelled, maka tombol tidak akan tampil.

4. Pembayaran otomatis tidak menghasilkan link/token

-   Tunggu beberapa menit, lalu cek halaman order.
-   Jika masih tidak muncul, hubungi support sertakan nomor order dan screenshot.

5. Upload file print-service gagal

-   Periksa ukuran file (biasanya ada batas) dan tipe file (PDF/JPG/PNG).

6. Duplikat order (double order)

-   Jika Anda menduga tercipta lebih dari satu order karena klik ganda, hubungi support segera.

7. Saya sudah transfer tapi status belum berubah

-   Upload bukti di halaman order -> Confirm
-   Jika sudah upload, hubungi support sertakan bukti transfer dan nomor order.

---

## Bagian J — Template pesan ke support (langsung copy-paste)

Subject: [Support] Permasalahan Order #12345 - [singkat masalah]

Halo Support,

Nama: [Nama Anda]
Email: [email Anda]
Order: #12345
Masalah: [jelaskan singkat dan kronologis]
Langkah yang sudah dicoba: [contoh: refresh page, upload bukti, cek inbox]
Lampiran: [screenshot halaman order, bukti transfer, link sesi print service jika ada]

Mohon bantuannya.

Terima kasih,
[Nama Anda]

---

## Lampiran teknis singkat (untuk referensi bila perlu)

Catatan singkat mapping URL -> perilaku (agar Anda tahu link mana untuk apa):

-   /orders/checkout — halaman checkout (GET) & submit order (POST ke same route)
-   /orders — daftar order
-   /orders/{id} — detail order
-   /orders/received/{id} — halaman terima order setelah sukses
-   /print-service/{token} — halaman sesi print-service untuk unggah & checkout
-   /smart-print — halaman generator sesi print-service
-   /barcode/preview — preview barcode (admin)
-   POST /payments/notification — internal webhook Midtrans

---

Jika Anda ingin panduan lebih panjang lagi (contoh: panduan step-by-step dengan gambar untuk setiap tombol), sebutkan halaman apa yang mau diilustrasikan (mis: Checkout, Smart Print, My Orders). Saya bisa menambah screenshot placeholders atau langsung menambahkan gambar hasil tangkapan layar.

Saya akan menandai pekerjaan dokumentasi pelanggan ini selesai di todo list. Jika mau, saya dapat:

-   Menambah 50–200 screenshot placeholders di bagian yang relevan.
-   Membuat versi ringkasan satu halaman.
-   Membuat panduan admin (lebih teknis) bila diperlukan.

---

## Memulai (akun & login)

-   Mengapa buat akun?

    -   Menyimpan alamat dan memudahkan checkout berikutnya.
    -   Melihat riwayat pesanan dan melanjutkan pesanan yang belum dibayar.

-   Cara membuat akun:

    1. Buka: /register
    2. Isi Nama, Email, Password -> klik Daftar.
    3. Jika perlu verifikasi email, buka inbox dan klik link.

-   Login:
    -   Buka /login, masukkan email & password.

Tips singkat: jika lupa password, gunakan fitur Lupa Password di halaman login.

---

## Menemukan produk & menambahkan ke keranjang

1. Cari produk

    - Gunakan kotak pencarian di halaman utama atau jelajahi kategori.

2. Tipe produk yang biasa Anda temui

    - Simple product: produk tunggal (pilih qty lalu Tambah ke Keranjang).
    - Configurable product: produk dengan pilihan (mis. warna/ukuran). Pilih varian dulu lalu tambah ke keranjang.

3. Saat stok habis

    - Sistem akan memberi tahu; pilih varian lain atau tunggu restock.

4. Melihat detail sebelum tambah ke keranjang
    - Klik foto / judul produk -> periksa gambar, deskripsi, dan pilihan varian.

---

## Keranjang (Cart)

-   Buka keranjang: /carts
-   Di sini Anda bisa:
    -   Mengubah jumlah tiap item
    -   Menghapus item
    -   Melihat subtotal dan estimasi ongkir (jika memilih kirim kurir)
    -   Lanjut ke Checkout

Catatan: keranjang disimpan di browser/session. Jika pindah perangkat, keranjang mungkin berbeda.

---

## Checkout — langkah demi langkah

URL: /orders/checkout

1. Persyaratan

    - Harus login untuk melakukan checkout.
    - Pastikan alamat & nomor telepon sudah benar.

2. Isi data pengiriman

    - Pilih Delivery Method:
        - Self: ambil langsung di toko (gratis).
        - Courier: kirim ke alamat (pilih provinsi/kota/kecamatan supaya ongkir terhitung).

3. Pilih metode pembayaran

    - Manual bank transfer (upload bukti)
    - Automatic (Midtrans): bayar via e-wallet, transfer bank otomatis, kartu
    - COD (jika tersedia)

4. Periksa ringkasan lalu klik "Place Order"

    - Pastikan subtotal, ongkir, dan total sudah benar.
    - Sistem akan membuat pesanan dan memberi nomor order (contoh: #VS-2025-123).

5. Setelah klik Place Order
    - Jika memilih Automatic, Anda akan diarahkan ke halaman pembayaran.
    - Jika Manual, ikuti instruksi transfer dan upload bukti ketika diminta.

Keamanan: sistem hanya mengizinkan pemilik akun melihat dan melanjutkan pesanan mereka.

---

## Resume Checkout (lanjutan bila tab tertutup atau belum selesai)

Situasi umum: Anda menutup tab/komputer mati setelah membuat order tapi belum membayar. Jangan khawatir — biasanya pesanan tersimpan dengan status unpaid/waiting.

Cara melanjutkan:

1. Buka My Orders: /orders
2. Cari pesanan yang statusnya "unpaid" atau "waiting" (biasanya ditandai)
3. Klik tombol Resume di baris pesanan tersebut
4. Anda akan dibawa kembali ke halaman checkout dengan data yang tersimpan. Lanjutkan pembayaran seperti biasa.

Catatan penting:

-   Anda hanya bisa melanjutkan jika pesanan belum dibayar, belum diselesaikan, dan belum dibatalkan.
-   Jika pesanan sudah dibayar atau statusnya "completed/cancelled", tombol Resume tidak akan muncul.

---

## Metode pembayaran & konfirmasi bukti

1. Pembayaran otomatis (Midtrans)

    - Pilih "Automatic" lalu lanjutkan. Sistem akan mengarahkan Anda ke halaman pembayaran (Midtrans).
    - Selesaikan pembayaran di halaman gateway. Jika berhasil, status pesanan akan berubah otomatis.

2. Transfer manual (bank transfer)

    - Pilih manual, lakukan transfer ke rekening yang tercantum.
    - Setelah transfer, buka halaman pesanan lalu upload foto bukti transfer.
    - Admin akan memeriksa bukti dan menandai pembayaran jika valid.

3. Jika status tidak berubah segera

    - Tunggu beberapa menit; terkadang notifikasi pembayaran membutuhkan waktu.
    - Jika lebih dari 30 menit belum berubah, hubungi support dengan nomor order dan screenshot bukti pembayaran.

4. Upload bukti: lokasi & langkah
    - Masuk ke My Orders -> klik Confirm atau Details pada order -> ada tombol untuk upload bukti.

---

## Smart Print / Print-service (unggah file dan cetak)

Layanan cetak memungkinkan Anda mengunggah file (PDF/JPG/PNG) lalu memesan cetak untuk berbagai jenis kertas.

Alur singkat (untuk pelanggan):

1. Buka menu Smart Print atau /smart-print
2. Pilih produk kertas (paper product) dan varian (mis. A4, warna/bw)
3. Klik Create/Generate Session — sistem akan memberi satu link khusus: /print-service/{token}
    - Simpan link ini jika ingin kembali nanti.
4. Di halaman sesi cetak (/print-service/{token}) unggah file Anda. Setiap file akan muncul sebagai bagian dari sesi.
5. Sistem menghitung harga berdasarkan jumlah halaman, jenis cetak, dan qty.
6. Klik Checkout di halaman print-service — prosesnya mirip checkout biasa.

Hal-hal penting yang perlu diperhatikan:

-   Sesi cetak bisa disimpan sementara. Jika Anda menutup tab, nantinya bisa membuka My Orders untuk mencari print order dengan status unpaid/payment_pending dan melanjutkan.
-   Admin akan memproses file setelah pembayaran dikonfirmasi.
-   Status yang umum Anda lihat: pending_upload (belum selesai unggah) -> uploaded -> payment_pending -> payment_confirmed -> ready_to_print -> printing -> printed -> completed.

---

## Barcode & label (untuk pelanggan)

-   Beberapa toko menyediakan label barcode untuk produk/ pesanan.
-   Sebagai pembeli, Anda biasanya tidak perlu mengurus pembuatan barcode — itu tugas admin. Tetapi:
    -   Jika Anda butuh label (mis. untuk print-service), gunakan fitur Smart Print untuk membuat file label.
    -   Untuk verifikasi di toko fisik, tunjukkan invoice/nomor order; kasir akan memproses sesuai kebutuhan.

---

## Melihat status pesanan & invoice

1. My Orders (daftar pesanan)

    - Buka: /orders
    - Di daftar ini Anda dapat melihat ringkasan order, status, dan tombol aksi (Details, Resume, Confirm).

2. Detail order

    - Klik Details untuk melihat alamat, item, ongkir, dan tautan download invoice.
    - Untuk mengunduh invoice (PDF), cari tombol "Invoice" atau "Download Invoice" pada halaman detail.

3. Cek status pengiriman
    - Jika pesanan sudah dikirim, nomor resi biasanya tampil di halaman detail order.

---

## Masalah umum & cara cepat mengatasinya

1. Ongkir tidak muncul

    - Pastikan Anda memilih provinsi, kota, dan kecamatan pada form pengiriman.

2. Tidak bisa resume checkout

    - Periksa status order: hanya pesanan yang belum dibayar (unpaid/waiting) bisa dilanjutkan.
    - Jika Anda yakin error, hubungi support.

3. Pembayaran otomatis gagal / token tidak muncul

    - Coba lagi beberapa saat.
    - Jika tetap gagal, hubungi support sertakan nomor order dan screenshot.

4. Upload file di print-service error

    - Periksa ukuran dan format file (umumnya PDF/JPG/PNG; ukuran maksimal akan diberi tahu di halaman unggah).

5. Duplikasi pesanan (double order)
    - Jika terlanjur membuat dua order karena klik ganda, kontak support untuk membantu menggabungkan atau membatalkan salah satunya.

---

## Hubungi support — template pesan yang praktis

Gunakan template ini agar support cepat membantu:

Subject: [Support] Permasalahan Order #12345

Hallo Support,

Nama: [Nama Anda]
Email: [email Anda]
Nomor Order: #12345
Masalah: [jelaskan singkat, contoh: "Saya menutup tab saat checkout; sekarang tidak bisa melanjutkan pembayaran"]
Lampiran: [screenshot invoice / bukti pembayaran / halaman error]

Terima kasih,
[Nama Anda]

---

## Tips & trik singkat

-   Simpan nomor order dan screenshot pembayaran — ini mempercepat proses support.
-   Jika menggunakan Smart Print: simpan link sesi /print-service/{token} jika Anda ingin kembali nanti.
-   Periksa email dan folder spam untuk email konfirmasi transaksi.

---

## Lampiran: status singkat yang sering muncul

-   Order status (umum): created -> confirmed -> shipped -> delivered -> completed
-   Payment status: unpaid / waiting -> paid

