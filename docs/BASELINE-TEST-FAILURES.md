# Kegagalan Baseline Test

## Ringkasan

Hasil full suite terakhir pada 1 November 2026: **555 test, 547 lulus, 8 gagal**. Delapan kegagalan pada run ini sama dengan daftar baseline sebelumnya; seluruh tes reward yang ditambahkan lulus. Kegagalan berikut berada di luar perubahan reward dan tetap menjadi catatan triase, bukan pernyataan bahwa kegagalan tersebut sudah diperbaiki.

## Daftar kegagalan

| # | Test | File | Hasil aktual |
|---|---|---|---|
| 1 | `test_admin_search_returns_grouped_results` | [AdminBannerTest.php](../tests/Feature/AdminBannerTest.php) | Endpoint pencarian admin mengembalikan `403`, sedangkan test mengharapkan `200`. |
| 2 | `test_admin_search_requires_minimum_characters` | [AdminBannerTest.php](../tests/Feature/AdminBannerTest.php) | Endpoint pencarian admin mengembalikan `403`, sedangkan test mengharapkan `200` dan JSON `{"groups":[]}`. |
| 3 | `test_component_showcase_table_still_uses_client_side_search` | [AdminTablePaginationTest.php](../tests/Feature/AdminTablePaginationTest.php) | Halaman showcase admin mengembalikan `403`, sedangkan test mengharapkan `200` sebelum memeriksa pencarian sisi klien. |
| 4 | `test_product_card_falls_back_to_the_category_icon_without_a_logo` | [BrandLogoDisplayTest.php](../tests/Feature/BrandLogoDisplayTest.php) | HTML kartu produk tidak memenuhi assertion fallback ikon kategori (`<span class="ms ...">`) dan tidak adanya `pcard-logo`. |
| 5 | `test_operator_mismatch_is_rejected` | [CheckoutOperatorMismatchTest.php](../tests/Feature/CheckoutOperatorMismatchTest.php) | Session tidak memiliki error validasi `target`; order tetap diharapkan tidak tercipta. |
| 6 | `test_ticket_page_shows_coming_soon_when_disabled` | [FeatureComingSoonTest.php](../tests/Feature/FeatureComingSoonTest.php) | Halaman tiket tidak menampilkan teks `Bantuan & Tiket segera hadir` saat fitur dinonaktifkan. |
| 7 | `test_reseller_page_shows_coming_soon_when_disabled` | [FeatureComingSoonTest.php](../tests/Feature/FeatureComingSoonTest.php) | Halaman reseller tidak menampilkan teks `Reseller segera hadir` saat fitur dinonaktifkan. |
| 8 | `test_brand_page_filter_uses_modal_like_product_index` | [ProductBrandPageTest.php](../tests/Feature/ProductBrandPageTest.php) | Halaman brand tidak memenuhi assertion pemicu modal filter dan urutan field `region`, `category`, `min_price`, `max_price`. |

## Catatan tindak lanjut

- Tiga kegagalan `403` perlu ditriase pada autentikasi/permission dan setup admin yang digunakan oleh test.
- Kegagalan checkout perlu ditinjau terhadap aturan operator-prefix untuk target dan brand.
- Kegagalan tampilan perlu dicocokkan antara kontrak test dan markup/fitur UI yang sekarang berlaku.
- Jangan menganggap suite siap rilis sampai setiap kegagalan sudah diperbaiki atau secara eksplisit dinyatakan sebagai perilaku yang memang diharapkan dan test-nya diselaraskan.
