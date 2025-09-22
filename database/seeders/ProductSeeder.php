<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $products = [
            // Kategori: Cetak
            ['id' => 3, 'name' => 'PRINT ON DEMAND SPESIALIS CETAK HVS', 'short_description' => 'UP TO A3+ (33CM X 48CM) SPEED UP TO 100PPM FULL COLOUR'],
            ['id' => 4, 'name' => 'PETA A3', 'short_description' => 'Kertas HVS 70-80 Gsm A3, Menggunakan Inkjet Pigment anti luntur apabila terkena air'],
            ['id' => 5, 'name' => 'KUESIONER & PRELIST', 'short_description' => 'Kertas HVS 70-80 Gsm, Ukuran Double F4 Landscape / A3, Lipat Tengah, Staples'],
            ['id' => 6, 'name' => 'SPIRAL NOTE BOOK', 'short_description' => 'Kertas HVS 70-80 Gsm A4/ A5, Sampul Artpaper, Isi sesuai pesanan'],
            ['id' => 7, 'name' => 'SPIRAL NOTEPAD', 'short_description' => 'Kertas HVS 70-80 Gsm A6, Sampul Artpaper, Isi sesuai pesanan'],
            ['id' => 8, 'name' => 'BUKU SOFTCOVER', 'short_description' => 'Kertas HVS 70-80 Gsm A4 / A5 / B5, Finishing Sampul Softcover doff/glossy'],
            ['id' => 9, 'name' => 'AMPLOP', 'short_description' => 'Amplop Paperline 80 gsm 23x11 cm dicetak dengan tinta pigment anti luntur, Amplop Coklat Folio dan Airmail'],
            ['id' => 10, 'name' => 'MAP CUSTOM', 'short_description' => 'Map Ukuran Folio, Didalam map terdapat penahan kertas'],
            ['id' => 11, 'name' => 'FLYER / LEAFLET', 'short_description' => 'Kertas ArtPaper 120gsm,150gsm, Cetak Bolak-Balik, Lipat 2/3'],
            ['id' => 18, 'name' => 'KALENDER DINDING', 'short_description' => 'Kertas AP A3 120 - 230 Gsm, Isi 6 - 13 Lembar tergantung bulan, Jilid Spiral Kawat / Plat'],
            ['id' => 19, 'name' => 'KALENDER DUDUK', 'short_description' => 'Kertas Ukuran A5 210 - 230 Gsm, Isi 7 - 13 Lembar tergantung bulan, Jilid Spiral Kawat, Stand Hardcover'],
            ['id' => 20, 'name' => 'PIAGAM', 'short_description' => 'Bahan Akrilik. Model bebas request sesuai kebutuhan'],
            
            // Kategori: Bag
            ['id' => 21, 'name' => 'TOTEBAG KANVAS', 'short_description' => 'Totebag bahan kanvas, Tali dan finishing velcro, Ukuran 32x38cm, Branding Sablon'],
            ['id' => 22, 'name' => 'TOTEBAG BLACU 1', 'short_description' => 'Totebag bahan Blacu Broken White, Tali dan finishing velcro, Ukuran 32x38cm, Branding Sablon'],
            ['id' => 23, 'name' => 'TOTEBAG BLACU 2', 'short_description' => 'Totebag bahan Blacu Broken White, Batik 1sisi di bagian bawah, Tali dan finishing Reseleting, Ukuran 32 x 38 cm, Branding Sablon'],
            ['id' => 24, 'name' => 'TOTEBAG DENIM', 'short_description' => 'Totebag bahan denim dengan lapisan dalam, Finishing reseleting, Ukuran 32 x 38 cm, Branding Sablon'],
            ['id' => 25, 'name' => 'TAS BRAND 1', 'short_description' => 'Bahan: Kanvas D300, Dimensi: 31x13x43 cm, Warna: Biru & Black, Branding: Sablon'],
            ['id' => 26, 'name' => 'TAS BRAND 2', 'short_description' => 'Bahan: Kanvas Denim, Dimensi: 31x13x46 cm, Warna: Navy & Black, Branding: Sablon'],
            ['id' => 27, 'name' => 'TAS BRAND 3', 'short_description' => 'Bahan: Kanvas D300, Dimensi: 31x13x43 cm, Warna: Blue, Green & Black, Branding: Sablon'],
            ['id' => 28, 'name' => 'TAS BRAND 4', 'short_description' => 'Bahan: Kanvas D300, Dimensi: 31x13x43 cm, Warna: Navy & Black, Branding: Sablon'],
            ['id' => 29, 'name' => 'TAS BRAND 5', 'short_description' => 'Bahan: Kanvas Denim, Dimensi: 30x11x41 cm, Warna: Red, Brown, Gray, Blue, Branding: Sablon'],
            ['id' => 30, 'name' => 'TAS BRAND 6', 'short_description' => 'Bahan: Kanvas D300, Dimensi: 31x13x43 cm, Warna: Green + Black, Branding: Sablon'],
            ['id' => 31, 'name' => 'TAS BRAND 7', 'short_description' => 'Bahan: Kanvas Denim, Dimensi: 31x13x43 cm, Warna: Brown, Branding: Sablon'],
            ['id' => 32, 'name' => 'TAS BRAND 8', 'short_description' => 'Bahan: Kanvas Denim, Dimensi: 31x13x43 cm, Warna: Navy, Branding: Sablon'],
            ['id' => 33, 'name' => 'TAS BRAND 9', 'short_description' => 'Bahan: Kanvas D300, Dimensi: 31x13x43 cm, Warna: Black, Branding: Sablon'],
            ['id' => 34, 'name' => 'TAS BRAND 10', 'short_description' => 'Bahan: Kanvas D300, Dimensi: 31x13x43 cm, Warna: Black, Branding: Sablon'],
            ['id' => 35, 'name' => 'TAS BRAND 11', 'short_description' => 'Bahan: Kanvas D300, Dimensi: 31x13x43 cm, Warna: Green + Black, Branding: Sablon, *) Untuk Desain lain bisa request'],

            // Kategori: Slingback
            ['id' => 36, 'name' => 'SLINGBAG 1', 'short_description' => 'Bahan: Kanvas D300, Dimensi: 22x6x25 cm, Warna: Gray + Black, Branding: Sablon'],
            ['id' => 37, 'name' => 'SLINGBAG 2', 'short_description' => 'Bahan: Kanvas D300, Dimensi: 27x7x17 cm, Warna: Green + Black, Branding: Sablon'],

            // Kategori: Pouch
            ['id' => 38, 'name' => 'POUCH A', 'short_description' => 'Pouch bahan kulit sintetis cleo/cesar dengan tutup kotak bermagnet, Lapisan dalam berbahan drill, 1 kompartemen utama bereseleting dengan 3 kantung bagian dalam 1 reseleting dan 2 kantung, dan tali kulit di bagian samping dan Free Branding'],
            ['id' => 39, 'name' => 'POUCH B', 'short_description' => 'Pouch bahan kulit sintetis dengan 2 kompartemen utama, 1 kompartemen luar, 3 kompartemen kecil bagian dalam dan tutup magnet.'],
            ['id' => 40, 'name' => 'POUCH C', 'short_description' => 'Pouch Kanvas dengan kombinasi kulit sintetis. Memiliki 2 kompartment berresleting dengan tambahan handle kulit sintetis sehingga mudah digenggam.'],
            
            // Kategori: ATK
            ['id' => 12, 'name' => 'TUMBLER CUSTOM A/B', 'short_description' => 'Tumbler LED, Tumbler Biasa, Branding Sablon UV'],
            ['id' => 13, 'name' => 'PAYUNG CUSTOM', 'short_description' => 'Payung Lipat, Payung Biasa, Branding Sablon'],
            ['id' => 14, 'name' => 'MUG CUSTOM', 'short_description' => 'Gelas, Branding Sablon'],
            ['id' => 15, 'name' => 'TOPI CUSTOM', 'short_description' => 'Topi Kain, Branding Sablon'],
            ['id' => 16, 'name' => 'JAM DINDING CUSTOM', 'short_description' => 'Jam Dinding, Branding Sablon'],
            ['id' => 17, 'name' => 'PIN GANCI CUSTOM', 'short_description' => 'Pin Plastik, Branding Sablon'],
            ['id' => 41, 'name' => 'ATK PAKET A', 'short_description' => 'Terdiri dari : 1 Buku Notes Paperline, 1 Ballpoint Snowman V5, 1 Pensil Mekanik Joyko, 1 Isi Pensil Joyko, 1 Penghapus Joyko, 1 Nametag B3 + Tali BIG'],
            ['id' => 42, 'name' => 'ATK PAKET B', 'short_description' => 'Terdiri dari : 1 Buku Notes Paperline, 1 Ballpoint Snowman V5, 2 Pensil Staedler, 1 Rautan Greebel, 1 Penghapus Joyko, 1 Nametag B3 + Tali BIG'],
            ['id' => 43, 'name' => 'ATK PAKET C', 'short_description' => 'Terdiri dari : 1 Buku Notes Paperline, 1 Ballpoint Standar JR6, 2 Pensil Greebel, 1 Rautan Greebel, 1 Penghapus BIG, 1 Nametag B3 + Tali BIG'],
            ['id' => 44, 'name' => 'ATK PAKET D', 'short_description' => 'Terdiri dari : 1 Map Jaring Resleting, 1 Buku Notes Paperline, 1 Ballpoint Snowman V5, 1 Pensil Mekanik Joyko, 1 Isi Pensil Joyko, 1 Penghapus Joyko, 1 Nametag B3 + Tali BIG, *) Bisa Costum bundling dengan ATK/ Barang lain'],
            ['id' => 45, 'name' => 'SEMINAR KIT PAKET A', 'short_description' => 'Terdiri dari : 1 Tas Pouch, 1 Buku Agenda Hardcover, 1 Ballpoint Gel, 1 Kardus Packing'],
            ['id' => 46, 'name' => 'SEMINAR KIT PAKET B', 'short_description' => 'Terdiri dari : 1 Tas Selempang, 1 Buku Notes Paperline, 1 Ballpoint Gel, 1 Nametag B3 + Tali BIG'],
            ['id' => 47, 'name' => 'SEMINAR KIT PAKET C', 'short_description' => 'Terdiri dari : 1 Tas Selempang, 1 Buku Notes Paperline, 1 Ballpoint Gel, 1 Nametag B3 + Tali BIG, *) Bisa Costum bundling dengan ATK/ Barang lain'],
            ['id' => 48, 'name' => 'SURVEY KIT PAKET TAS A', 'short_description' => 'Terdiri dari : 1 Tas Backpack Canvas, 1 Buku Blocknote Paperline 50, 1 Ballpoint Standard AE 7, 2 Pensil Greebel 2B, 1 Penghapus BIG, 1 Rautan Greebel, 1 Name tag + Tali BIG'],
            ['id' => 49, 'name' => 'SURVEY KIT PAKET TAS B', 'short_description' => 'Terdiri dari : 1 Tas Backpack Canvas, 1 Buku Blocknote Paperline 50, 1 Ballpoint Standard AE 7, 2 Pensil Greebel 2B, 1 Penghapus BIG, 1 Rautan Greebel, 1 Name tag + Tali BIG'],
            ['id' => 50, 'name' => 'SURVEY KIT PAKET TAS C', 'short_description' => 'Terdiri dari : 1 Tas Backpack Canvas, 1 Buku Blocknote Paperline 50, 1 Ballpoint Standard AE 7, 2 Pensil Greebel 2B, 1 Penghapus BIG, 1 Rautan Greebel, 1 Name tag + Tali BIG, *) Bisa Costum bundling dengan ATK/ Barang lain'],
            
            // Kategori: Elektronik
            ['id' => 71, 'name' => 'CANON iP2770', 'short_description' => 'Spesifikasi : Print Only, A4 Color/Mono, USB Input'],
            ['id' => 72, 'name' => 'CANON MG2570s', 'short_description' => 'Spesifikasi : Print , Scan, Copy, A4 Color/Mono, USB Input'],
            ['id' => 73, 'name' => 'CANON e410', 'short_description' => 'Spesifikasi : Print , Scan, Copy, A4 Color/Mono, USB Input'],
            ['id' => 74, 'name' => 'CANON G4770', 'short_description' => 'Spesifikasi : Print , Scan, Copy, ADF, A4 Color/Mono, USB Input'],
            ['id' => 75, 'name' => 'CANON G2770', 'short_description' => 'Spesifikasi : Print, Scan, Copy, A4 Color/Mono, USB Input'],
            ['id' => 76, 'name' => 'CANON LBP 6030', 'short_description' => 'Spesifikasi : Print Only, A4 Mono, USB Input'],
            ['id' => 77, 'name' => 'HP INKTANK 315', 'short_description' => 'Spesifikasi : Print , Scan, Copy, A4 Color/Mono, USB Input'],
            ['id' => 78, 'name' => 'HP 2335', 'short_description' => 'Spesifikasi : Print , Scan, Copy, A4 Color/Mono, USB Input'],
            ['id' => 79, 'name' => 'HP 2775', 'short_description' => 'Spesifikasi : Print , Scan, Copy, A4 Color/Mono, USB Input'],
            ['id' => 80, 'name' => 'BROTHER T220', 'short_description' => 'Spesifikasi : Print , Scan, Copy, A4 Color/Mono, USB Input'],
            ['id' => 81, 'name' => 'BROTHER T420W', 'short_description' => 'Spesifikasi : Print , Scan, Copy, A4 Color/Mono, USB, WIFI Input'],
            ['id' => 82, 'name' => 'BROTHER B2080DW', 'short_description' => 'Spesifikasi : Print Only, A4 Mono/ Duplex, USB, WIFI Input'],
            ['id' => 83, 'name' => 'BROTHER DCP L2540DW', 'short_description' => 'Spesifikasi : Print , Scan, Copy, A4 Mono/ Duplex, USB, LAN, WIFI Input'],
            ['id' => 84, 'name' => 'BROTHER SCANNER DS 6402', 'short_description' => 'Spesifikasi : Scan only/ Luas scan A4/ Speed up to 20 ppm/ USB Input'],
            ['id' => 85, 'name' => 'HP SCANJET 2000 S2', 'short_description' => 'Spesifikasi : Scan only/ Luas scan A4/ Speed up to 35 ppm/ USB Input'],
            ['id' => 86, 'name' => 'CANON SCANNER P208 II', 'short_description' => 'Spesifikasi : Scan only/ Luas scan A4/ Speed up to 10 ppm/ USB Input'],
            ['id' => 87, 'name' => 'ZEBRA ZD230', 'short_description' => 'Spesifikasi : Thermal Transfer (memakai ribbon) & Direct Thermal / USB Input'],
            ['id' => 88, 'name' => 'HONEYWELL HH490', 'short_description' => 'Spesifikasi : Barcode Scanner / USB'],
            ['id' => 89, 'name' => 'WACOM CTL 472', 'short_description' => 'Spesifikasi : Tablet with pressure-sensitive, cordless, battery-free pen'],
            ['id' => 90, 'name' => 'PROJECTOR OPTOMA X400LVE', 'short_description' => 'Spesifikasi : Brightness: 4,000 ANSI LUMENS, Resolution: 1024x768, Aspect Ratio: 4:3 (XGA), Input: HDMI, VGA, USB'],
            ['id' => 91, 'name' => 'HP PC 22-DD2009D AIO CEL J4025', 'short_description' => 'Spesifikasi : RAM 4GB / 256 GB SSD / 21.5 FHD / Windows 11 + OHS'],
            ['id' => 92, 'name' => 'HP PC 22-DD2010D AIO I3 - 1215U', 'short_description' => 'Spesifikasi : RAM 4G / 512GB SSD / 21,5 FHD / Windows 11 + OHS'],
            ['id' => 93, 'name' => 'HP PRO 200 G4 I3-10110U', 'short_description' => 'Spesifikasi : RAM 4GB/ HDD 1TB/ LCD 21.5 FHD / Windows 10'],
            ['id' => 94, 'name' => 'HP PRO 200 G4 I5-10210U', 'short_description' => 'Spesifikasi : RAM 8GB / HDD 1 TB / LCD 21.5 FHD / Windows 10 Pro'],
            ['id' => 95, 'name' => 'HP PRO 205 G4 ATHLON 3050U', 'short_description' => 'Spesifikasi : RAM 4GB / HDD 1TB / LCD 21.5 FHD, Windows 10'],
            ['id' => 96, 'name' => 'HP PC 24 AIO I5-1135G7', 'short_description' => 'Spesifikasi : RAM 8GB/ 512GB SSD Nvme/ LCD 23,8 IPS FHD/ Windows 11 Home+OHS'],
            ['id' => 97, 'name' => 'PC HP 280 PRO G5 SFF I3-10100', 'short_description' => 'Spesifikasi : RAM 4GB/ HDD 1TB/LCD HP P204V 19.5" FHD/ Windows 10 Home'],
            ['id' => 98, 'name' => 'PC HP PRODESK 400 G7 SFF', 'short_description' => 'Spesifikasi : Processor i3-10100T/ RAM 4GB/ HDD 1TB/ Windows 10 Home'],
            ['id' => 99, 'name' => 'HP 280 G6 PRO', 'short_description' => 'Spesifikasi : Processor i3-10100/ RAM 4GB/ HDD 1 TB/ WIN 10 HOME 64Bit'],
            ['id' => 100, 'name' => 'HP 280 G6 PRO (Intel Core i5-10400)', 'short_description' => 'Spesifikasi : Processor i5-10400/ RAM 8GB/ HDD 1 TB + 256 SSD/ WIN 10 PRO 64Bit'],
        ];

        $dataToInsert = array_map(function ($product) {
            // ensure sku exists because DB requires it
            if (empty($product['sku'])) {
                $base = Str::slug($product['name'], '-');
                $product['sku'] = isset($product['id']) ? $base . '-' . $product['id'] : $base . '-' . uniqid();
            }

            return array_merge([
                'type' => 'simple',
                'slug' => Str::slug($product['name'], '-'),
                'price' => 15000.00,
                'harga_beli' => 10000.00,
                'total_stock' => 100,
                'sold_count' => 0,
                'rating' => 0.00,
                'is_featured' => 0,
                'status' => 1,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], $product);
        }, $products);

        DB::table('products')->insert($dataToInsert);
    }
}