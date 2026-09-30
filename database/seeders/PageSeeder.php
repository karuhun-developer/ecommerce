<?php

namespace Database\Seeders;

use App\Models\Content\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['tentang-ecommerce', 'Tentang Ecommerce', 'Ecommerce menyediakan katalog produk dan layanan belanja online.', 'brand', 0],
            ['hak-kekayaan-intelektual', 'Hak Kekayaan Intelektual', 'Halaman ini memuat informasi mengenai hak kekayaan intelektual pada konten dan produk yang ditampilkan di Ecommerce.', 'brand', 1],
            ['karir', 'Karir', 'Informasi karir dan kesempatan bergabung dengan tim Ecommerce akan diperbarui di halaman ini.', 'brand', 2],
            ['blog', 'Blog', 'Temukan informasi produk, kabar terbaru, dan tips belanja dari Ecommerce di halaman ini.', 'brand', 3],
            ['tagihan-top-up', 'Tagihan & Top Up', 'Informasi ketersediaan layanan tagihan dan top up akan diperbarui di halaman ini.', 'buy', 0],
            ['tukar-tambah-handphone', 'Tukar Tambah Handphone', 'Informasi program tukar tambah handphone dan ketentuannya akan diperbarui di halaman ini.', 'buy', 1],
            ['pusat-edukasi-seller', 'Pusat Edukasi Seller', 'Panduan untuk penjual dalam mengelola toko, produk, dan pesanan di Ecommerce.', 'sell', 0],
            ['daftar-official-store', 'Daftar Official Store', 'Informasi pendaftaran dan persyaratan Official Store akan diperbarui di halaman ini.', 'sell', 1],
            ['mitra-ecommerce', 'Mitra Ecommerce', 'Temukan toko dan produk dari mitra Ecommerce melalui katalog produk.', null, 0],
            ['promo', 'Promo', 'Lihat banner di beranda dan katalog produk untuk menemukan penawaran yang tersedia.', null, 0],
            ['bantuan', 'Bantuan', 'Gunakan menu akun untuk melihat pesanan, alamat pengiriman, dan status pembayaran.', null, 0],
        ];

        foreach ($pages as [$slug, $title, $body, $group, $order]) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title, 'body' => '<p>'.$body.'</p>', 'published' => true,
                'footer_group' => $group, 'sort_order' => $order,
            ]);
        }
    }
}
