# Katalog Test Case — Atlantic Pedia Tab

Dokumen ini mencakup **semua positive & negative test case** untuk seluruh
fitur (user area & admin area), dikelompokkan per fitur, lengkap dengan
ekspektasi hasil dan referensi test file/method-nya.

**Cara menjalankan:**

```bash
php artisan test --compact                       # seluruh suite (371 test, 1244 assertions)
php artisan test --compact --filter=CheckoutTest # per file
php artisan test --compact --filter=test_login_  # per method
vendor/bin/phpunit tests/Feature/DepositTest.php # runner langsung
```

Keterangan kolom:

| Simbol | Arti |
|--------|------|
| ✅ | Positive case (alur normal harus berhasil) |
| ❌ | Negative case (alur salah/gagal harus ditolak dengan benar) |

---

# BAGIAN A — USER AREA

## A1. Registrasi & Sesi Awal

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A1.1 | ✅ | User baru mendaftar (nama, email, password) | User dibuat **unverified**, redirect ke halaman notice verifikasi email | `AuthFlowTest::test_registration_creates_an_unverified_user_and_redirects_to_the_notice` |
| A1.2 | ✅ | User baru mendaftar | Saldo = **0** dan poin = **0** | `AuthFlowTest::test_registration_starts_with_zero_balance_and_points` |
| A1.3 | ❌ | Guest akses halaman privat tanpa login | Redirect ke `/login` | `ExampleTest::test_guests_are_redirected_to_login` |
| A1.4 | ❌ | Halaman login dibuka tanpa sesi | Response 200, tidak menampilkan akun demo | `ExampleTest::test_login_page_*` |

## A2. Verifikasi Email

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A2.1 | ✅ | Klik link verifikasi (signed URL valid) | Email terverifikasi | `AuthFlowTest::test_user_can_verify_their_email_with_a_signed_link` |
| A2.2 | ✅ | Minta kirim ulang email verifikasi | Notifikasi terkirim ulang (throttle 6/menit) | `AuthFlowTest::test_the_verification_email_can_be_resent` |
| A2.3 | ❌ | User belum verifikasi akses halaman utama | Redirect ke `verification.notice` | `AuthFlowTest::test_unverified_user_is_redirected_to_the_verification_notice` |
| A2.4 | ✅ | Email verifikasi dikirim | Template bertema Atlantic Pedia + berisi link | `AuthEmailTest::test_verification_email_template_is_themed_and_contains_the_link` |
| A2.5 | ✅ | Notifikasi verifikasi via queue | Memakai mail view custom | `AuthEmailTest::test_verification_notification_uses_the_custom_mail_view` |

## A3. Login & Lupa Password

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A3.1 | ✅ | Login tanpa 2FA aktif | Langsung masuk (redirect sesuai role) | `AuthFlowTest::test_login_without_two_factor_logs_the_user_in` |
| A3.2 | ✅ | Login dengan 2FA aktif | Menampilkan challenge 2FA dulu | `AuthFlowTest::test_login_with_two_factor_requires_the_challenge` |
| A3.3 | ✅ | Minta link reset password | Email terkirim, halaman forgot render | `AuthFlowTest::test_the_forgot_and_reset_password_pages_render` + `test_a_password_reset_link_can_be_requested` |
| A3.4 | ✅ | Reset password dengan token valid | Password baru terpakai, bisa login | `AuthFlowTest::test_a_password_can_be_reset_with_a_valid_token` |
| A3.5 | ✅ | Email reset password dikirim | Template bertema + berisi link | `AuthEmailTest::test_reset_password_email_template_is_themed_and_contains_the_link` |
| A3.6 | ❌ | Tantangan 2FA dengan kode salah | Ditolak | `AuthFlowTest::test_two_factor_challenge_rejects_an_invalid_code` |
| A3.7 | ❌ | Buka halaman challenge 2FA tanpa pending login | Redirect/ditolak | `AuthFlowTest::test_the_two_factor_challenge_page_requires_a_pending_login` |
| A3.8 | ✅ | Klik "Login dengan Google" saat belum dikonfigurasi | Redirect kembali ke login (tidak error 500) | `AuthFlowTest::test_google_redirect_returns_to_login_when_not_configured` |

## A4. Produk, Katalog & Pencarian

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A4.1 | ✅ | Pencarian pascabayar tanpa memilih tab | Produk ketemu | `ProductSearchTest::test_search_finds_postpaid_products_*` |
| A4.2 | ✅ | AJAX search | Mengembalikan fragment tabs | `ProductSearchTest::test_ajax_search_returns_tabs_fragment` |
| A4.3 | ✅ | Filter harga (modal) | Produk terfilter sesuai rentang | `ProductSearchTest::test_filters_products_by_price_range` |
| A4.4 | ✅ | Load more 40 halaman per | Produk pakai pagination "load more" 40/halaman | `ProductSearchTest::test_product_list_uses_load_more_with_forty_per_page` |
| A4.5 | ❌ | Katalog/search/lookup kategori | **Pulsa & Data disembunyikan** dari katalog umum | `ProductSearchTest::test_catalog_hides_pulsa_and_data_products` dsb (5 test) |
| A4.6 | ❌ | Browse tanpa query | Tetap terfilter payment type tab aktif | `ProductSearchTest::test_browsing_without_a_query_filters_by_payment_type` |
| A4.7 | ✅ | Halaman brand | Render produk, filter region/harga/kategori, search by name/code, AJAX pagination | `ProductBrandPageTest` (17 test) |
| A4.8 | ❌ | Halaman brand tanpa login | Redirect login | `ProductBrandPageTest::test_brand_page_requires_auth` |
| A4.9 | ✅ | Kartu produk | Logo brand tampil; fallback ikon kategori bila tak ada logo | `BrandLogoDisplayTest` (2 test) |

### Riwayat Pencarian

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A4.10 | ✅ | Search full-page | Kata kunci tercatat di riwayat | `SearchHistoryTest::test_full_page_search_records_history` |
| A4.11 | ❌ | Search AJAX | **Tidak** dicatat (hindari noise) | `SearchHistoryTest::test_ajax_search_does_not_record_history` |
| A4.12 | ✅ | Query sama diulang | Tidak duplikat, hit bertambah, maks 10 riwayat/user | `test_remember_increments_hits`, `test_same_query_is_not_duplicated`, `test_history_is_pruned_to_ten_per_user` |
| A4.13 | ❌ | Query kosong / terlalu panjang / bersihkan riwayat orang lain / guest clear | Diabaikan atau hanya hapus milik sendiri | `test_blank_or_too_long_query_is_ignored`, `test_clear_endpoint_only_removes_own_history`, `test_guest_cannot_clear_search_history` |
| A4.14 | ✅ | Dashboard & riwayat pencarian | Riwayat terbaru tampil; top query terurut; rekomendasi mengikuti top query; **hilang** saat tak ada riwayat; survive setelah riwayat dihapus | 5 test `test_dashboard_*` |

## A5. Deteksi Operator & Nickname (Pulsa/Data/Game)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A5.1 | ✅ | Deteksi nomor Telkomsel / ByU (prefix 0879) | Operator benar + URL logo brand | `PhoneOperatorTest::test_detects_telkomsel_operator`, `test_detects_byu_operator_with_sub_prefix` |
| A5.2 | ✅ | Cek nickname game didukung (mis. ML + zone) | Nickname terbaca; zone dikirim sebagai `server` | `NicknameLookupTest` + `GameNicknameTest` |
| A5.3 | ❌ | Nomor tak dikenal / brand tidak didukung / target kosong / guest | Error message, tanpa panggilan API, ditolak | `PhoneOperatorTest::test_returns_error_for_unknown_operator`, `GameNicknameTest::test_unsupported_brands_do_not_call_the_api`, `NicknameLookupTest::test_target_is_required`, `test_guest_cannot_check_a_nickname` |
| A5.4 | ❌ | ML tanpa zone | Error tanpa memanggil API | `GameNicknameTest::test_mobile_legends_requires_a_zone_without_calling_the_api` |
| A5.5 | ❌ | Nickname akun tidak ditemukan | Pesan "tidak ditemukan" | `GameNicknameTest::test_it_reports_when_the_account_is_not_found` |
| A5.6 | ✅ | Halaman pulsa | Guest redirect; nomor diminta dulu sebelum paket muncul; tab sesuai operator; filter & sort harga; link dashboard | `PulsaPageTest` (7 test, termasuk ❌ guest redirect) |

## A6. Checkout & Order (User)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A6.1 | ✅ | Halaman checkout | Menampilkan produk & label target | `CheckoutTest::test_checkout_page_shows_the_product_and_target_label` |
| A6.2 | ✅ | Checkout saldo (balance) | Order **Sukses** langsung, saldo berkurang | `test_balance_checkout_creates_a_successful_order_and_deducts_balance` |
| A6.3 | ✅ | Checkout QRIS | Order **Pending**, saldo **tidak** berkurang | `test_qris_checkout_creates_a_pending_order_without_deducting_balance` |
| A6.4 | ✅ | Checkout VA | Wajib punya `master_bank` aktif, pakai bank yang match | `test_virtual_account_requires_a_master_bank_and_rejects_a_mismatched_one` |
| A6.5 | ✅ | Checkout E-Wallet | Pakai master bank bertipe e-wallet yang aktif | `test_ewallet_checkout_uses_an_active_ewallet_master_bank` |
| A6.6 | ✅ | Checkout pulsa/data | Order dibuat & fulfillment dispatch | `test_pulsa_and_data_products_can_be_checked_out` |
| A6.7 | ✅ | Beli pulsa dari halaman pulsa | Order `processing`, job fulfillment jalan | `PulsaCheckoutTest::test_buying_pulsa_creates_a_processing_order_and_dispatches_fulfillment` |
| A6.8 | ✅ | Konfirmasi bayar order pending (manual transfer) | Order jadi lunas/sukses + log paid | `test_pending_order_can_be_confirmed_as_paid` + `test_confirming_payment_records_a_paid_log` |
| A6.9 | ✅ | Prefill target & zone dari produk ke checkout | Form terisi otomatis | `test_checkout_prefills_target_and_zone_from_the_product_page` |
| A6.10 | ✅ | Dashboard & halaman saldo | Saldo real setelah checkout terlihat | `test_dashboard_shows_the_users_real_balance_after_checkout`, `test_saldo_page_shows_the_real_balance_and_recent_orders` |
| A6.11 | ❌ | Checkout tanpa target | Validasi error | `test_checkout_requires_a_target` |
| A6.12 | ❌ | Brand yang butuh zone dikirim tanpa zone | Validasi error | `test_checkout_requires_zone_for_brands_that_use_one` |
| A6.13 | ❌ | Saldo tidak cukup | Ditolak, saldo tidak berubah | `test_balance_checkout_rejects_when_balance_is_insufficient` |
| A6.14 | ❌ | Operator tidak match (nomor vs produk) | Ditolak | `CheckoutOperatorMismatchTest::test_operator_mismatch_is_rejected` |
| A6.15 | ❌ | Grosir/stok terakhir dibeli 2 device bersamaan | Hanya 1 yang berhasil (row lock) — **race condition** | `CheckoutGuardTest::test_the_last_unit_cannot_be_sold_twice`, `test_a_balance_cannot_fund_two_purchases_from_two_devices` |
| A6.16 | ❌ | Lihat/bayar order user lain | 404/403 (ownership) | `test_a_user_cannot_view_or_pay_another_users_order` |
| A6.17 | ❌ | Lihat invoice user lain | Ditolak | `test_a_user_cannot_view_another_users_order_invoice` |
| A6.18 | ✅ | Riwayat order | Hanya menampilkan milik sendiri | `test_history_lists_only_the_users_own_orders` |
| A6.19 | ✅ | Detail order produk aktif | Tombol **Beli Lagi** tampil & prefill target/zone | `OrderReorderTest` (3 test, ❌ bila produk inactive) |

### Error Checkout Ter-catat (Log)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A6.20 | ✅ | Checkout balance sukses | Log transaksi tercatat | `AdminTransactionTest::test_balance_checkout_records_transaction_logs` |
| A6.21 | ❌ | Checkout gagal | Log level warning tercatat | `test_failed_checkout_records_a_warning_log` |

## A7. Deposit / Top Up Saldo (User)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A7.1 | ✅ | Halaman top up | Metode + preset nominal tampil; admin fee tampil di opsi target | `DepositTest::test_top_up_page_shows_the_available_methods_and_presets`, `..._admin_fee_on_the_target_option` |
| A7.2 | ✅ | Buat pending top up | Deposit pending dibuat, saldo **tidak** berubah | `test_a_user_can_create_a_pending_top_up_without_touching_balance` |
| A7.3 | ✅ | Transfer bank pilih bank tertentu | `master_bank` tersimpan + admin fee ikut tersimpan | `test_transfer_bank_uses_the_selected_master_bank`, `..._stores_the_admin_fee_from_the_selected_bank` |
| A7.4 | ✅ | Halaman detail deposit VA/E-Wallet | Instruksi bank + tombol copy nomor/nominal + admin fee + total transfer | `test_deposit_show_displays_the_master_bank_instructions`, `..._offers_copy_for_number_and_amount`, `..._displays_the_admin_fee_and_total_transfer` |
| A7.5 | ✅ | Halaman detail deposit VA/E-Wallet | Menunggu konfirmasi admin (belum ada kredit otomatis) | `test_va_and_ewallet_deposit_page_waits_for_admin_confirmation` |
| A7.6 | ✅ | Simulasi pembayaran (webhook/test env) | Saldo bertambah **sekali** + ledger entry tercatat | `test_simulated_payment_adds_balance_and_records_a_ledger_entry` |
| A7.7 | ✅ | QRIS deposit | Tampil tanpa data "sandbox testing"; tombol download + cek status tersedia; bisa didownload | `test_deposit_show_shows_qris_without_sandbox_testing_data`, `..._offers_qris_download_and_status_buttons`, `test_deposit_qris_can_be_downloaded` |
| A7.8 | ✅ | Cek status deposit | Redirect dengan pesan status (throttle 10/menit) | `test_deposit_sync_redirects_with_status_message` |
| A7.9 | ✅ | Halaman top up / riwayat | Semua deposit user tampil; callout pending belum expired di saldo | `test_top_up_history_lists_all_deposits_for_the_user`, `..._callout_for_unexpired_pending_top_ups` |
| A7.10 | ✅ | Polling status | JSON status untuk polling; poll hook tampil hanya saat pending | `test_show_returns_json_status_for_polling`, `test_pending_top_up_page_renders_the_poll_hook` |
| A7.11 | ❌ | Nominal di luar limit | Validasi error | `test_amount_must_be_within_limits` |
| A7.12 | ❌ | VA tanpa target bank | Validasi error | `test_virtual_account_requires_a_bank_target` |
| A7.13 | ❌ | Target e-wallet dipilih bank (atau sebaliknya) | Ditolak | `test_ewallet_rejects_a_bank_type_target` |
| A7.14 | ❌ | Deposit sudah pernah dibayar | Tidak dikredit dua kali | `test_an_already_paid_deposit_is_not_credited_twice` |
| A7.15 | ❌ | Deposit expired dibayar | Ditolak | `test_an_expired_deposit_cannot_be_paid` |
| A7.16 | ❌ | Lihat/bayar deposit user lain | Ditolak (ownership) | `test_a_user_cannot_view_or_pay_another_users_deposit` |
| A7.17 | ❌ | Download QRIS deposit orang lain / tanpa qr_url | 403 / 404 | `test_deposit_qris_download_requires_ownership`, `..._returns_404_without_qr_url` |
| A7.18 | ❌ | Cek status deposit orang lain | Ditolak | `test_deposit_sync_requires_ownership` |
| A7.19 | ✅ | Mutasi saldo | User hanya melihat ledger miliknya; halaman saldo tertaut | `SaldoMutationTest` (3 test, ❌ guest redirect) |

## A8. Webhook Payment Gateway (Midtrans)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A8.1 | ✅ | Settlement notifikasi valid | Saldo dikredit + order di-settle & fulfillment jalan | `MidtransWebhookTest::test_a_settlement_notification_credits_the_balance`, `MidtransOrderTest::test_webhook_settles_order_and_starts_fulfillment_*` |
| A8.2 | ❌ | Signature tidak valid | Notifikasi **diabaikan** | `MidtransWebhookTest::test_a_notification_with_an_invalid_signature_is_ignored` |
| A8.3 | ✅ | Notifikasi expire | Deposit / order jadi expired | `test_an_expire_notification_marks_the_deposit_expired`, `MidtransOrderTest::test_webhook_expires_order` |
| A8.4 | ✅ | QRIS order | Data QRIS tampil tanpa "testing"; download QRIS; cek status; JSON polling | `MidtransOrderTest` (14 test; ❌ download milik orang lain 403, tanpa qr_url 404) |
| A8.5 | ❌ | Midtrans tidak dikonfigurasi | Charge/sync mengembalikan null (tidak crash) | `test_charge_order_qris_returns_null_when_not_configured` |

## A9. Poin, Tier & Merchandise (User)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A9.1 | ✅ | Poin < threshold Gold / tepat Gold / tepat Platinum / > Platinum | Tier Silver / Gold / Platinum sesuai | `UserTierTest` (6 test: 4 threshold + `cssClass` + `nextThreshold`) |
| A9.2 | ✅ | `cssClass()` & `nextThreshold()` | `tier-*` benar; Platinum → null (tier max) | `UserTierTest::test_tier_css_class_matches_value`, `test_next_threshold_returns_null_at_max_tier` |
| A9.3 | ✅ | Buka katalog merchandise | Katalog aktif + poin + tier tampil | `UserMerchandiseTest::test_user_can_view_the_merchandise_catalog` |
| A9.4 | ✅ | Tukar poin dengan cukup stok & poin | Poin berkurang, stok −1, redemption **Pending** dibuat | `test_user_can_redeem_points_for_merchandise` |
| A9.5 | ❌ | Poin tidak cukup | Error `points`, poin & stok tak berubah, tak ada redemption | `test_redeem_fails_when_points_are_insufficient` |
| A9.6 | ❌ | Stok habis | Error `merchandise`, tidak ada perubahan | `test_redeem_fails_when_stock_is_empty` |
| A9.7 | ❌ | Merchandise nonaktif | Error `merchandise`, tidak ada perubahan | `test_redeem_fails_when_merchandise_is_inactive` |
| A9.8 | ❌ | Item nonaktif | Tidak muncul di katalog | `test_inactive_items_are_hidden_from_the_catalog` |
| A9.9 | ✅ | Penukaran dibatalkan (via admin/refund) | Poin kembali, stok +1, status Cancelled | `test_cancelling_a_redemption_refunds_points_and_restocks` |
| A9.10 | ✅ | Dapat poin (order/topup) | Ledger `point_transactions` tercatat (delta + saldo after) | `PointLedgerTest::test_earning_a_point_is_recorded_in_the_ledger` |
| A9.11 | ✅ | Tukar merchandise | Ledger debit (−) + reference merchandise | `test_redeeming_merchandise_records_a_debit_entry` |
| A9.12 | ✅ | Refund penukaran | Ledger kredit (+) + saldo after sesuai | `test_cancelling_a_redemption_records_a_refund_entry` |
| A9.13 | ✅ | Ledger konsisten | Saldo after ledger terakhir = saldo user saat ini | `test_ledger_reflects_current_balance` |

## A10. Pengaturan Akun (User)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A10.1 | ✅ | Halaman pengaturan | Semua section terdaftar & tiap halaman detail render | `UserSettingsTest::test_the_settings_page_lists_every_section`, `test_each_settings_detail_page_renders` |
| A10.2 | ✅ | Update profil | Data tersimpan | `test_a_user_can_update_their_profile` |
| A10.3 | ✅ | Ganti password (password lama benar) | Password berubah | `test_a_user_can_change_their_password` |
| A10.4 | ✅ | Ganti email | Reset `email_verified_at` + kirim ulang verifikasi | `test_changing_email_resets_verification_and_resends_the_notification` |
| A10.5 | ✅ | Tambah/hapus rekening bank | Berhasil | `test_a_user_can_add_and_remove_a_bank_account` |
| A10.6 | ✅ | Aktifkan/confirm/disable 2FA | Alur penuh berhasil | `test_two_factor_can_be_enabled_confirmed_and_disabled` |
| A10.7 | ❌ | Ganti password tanpa password saat ini | Ditolak | `test_changing_password_requires_the_current_password` |
| A10.8 | ❌ | Rekening bank = nomor milik master bank | Ditolak (bentrok kebijakan) | `test_a_user_cannot_add_a_bank_account_matching_a_master_bank` |
| A10.9 | ❌ | Ubah rekening bank user lain | Ditolak | `test_a_user_cannot_modify_another_users_bank_account` |

## A11. Kontak & Device Token

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A11.1 | ✅ | CRUD kontak + import CSV + unduh template | Berhasil | `UserContactTest` (✅ 4 test; halaman butuh login ada di A11.2 ❌) |
| A11.2 | ❌ | Duplikat nomor untuk user sama / ubah kontak user lain / guest | Ditolak | `test_duplicate_phone_for_same_user_is_rejected`, `test_user_cannot_modify_another_users_contact` |
| A11.3 | ✅ | Register device token (push) | Token tersimpan; token sama tidak duplikat; token milik user lain di-transfer kepemilikannya; hapus token sendiri berhasil | `UserDeviceTokenTest` (✅ 4 test) |
| A11.4 | ❌ | Guest register token / hapus token user lain | Ditolak | `test_guest_cannot_register_a_device_token`, `test_user_cannot_remove_another_users_token` |

## A12. Notifikasi (User)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A12.1 | ✅ | Deposit di-settle / order sukses-gagal | Notifikasi terbuat untuk owner | `UserNotificationTest::test_settling_a_deposit_notifies_the_owner`, `..._creates_success_and_failure_notifications` |
| A12.2 | ✅ | Dropdown notifikasi | JSON + unread count; membuka → ditandai read & redirect (aman terhadap open-redirect host) | `test_notification_dropdown_returns_json_with_unread_count`, `test_opening_a_notification_marks_it_read_and_redirects`, `test_opening_a_notification_redirects_to_same_host_path` |
| A12.3 | ✅ | Halaman notifikasi | Tab read/unread, search, load more 10/halaman, list endpoint rows+footer | 6 test halaman notifikasi |
| A12.4 | ✅ | Notifikasi order produk aktif | Tombol **Beli Lagi** (dropdown + halaman) | 3 test `beli_lagi` |
| A12.5 | ✅ | Tandai semua dibaca | Berhasil | `test_user_can_mark_all_notifications_as_read` |
| A12.6 | ❌ | Buka notifikasi user lain / produk inactive (tombol sembunyi) / notifikasi non-order | Ditolak / tanpa Beli Lagi | `test_user_cannot_open_another_users_notification`, 2 test terkait |

## A13. Push Notification

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A13.1 | ✅ | Service worker & halaman settings notif | Route JS tersedia; halaman render | `PushNotificationTest` (2 test) |
| A13.2 | ✅ | Kirim transaksi sukses | Push terkirim, token basah (stale) di-prune | `test_transaction_result_is_pushed_and_stale_tokens_are_pruned` |
| A13.3 | ❌ | Firebase tidak dikonfigurasi / credentials hilang | Push di-skip (tidak error) | 2 test `push_is_skipped_*` |

## A14. Banner & Dashboard (User)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| A14.1 | ✅ | Dashboard | Banner aktif saja; banner gambar full-bleed + CTA; saldo & poin real; link pulsa/data; best seller | `DashboardBannerTest`, `DashboardWalletTest` |
| A14.2 | ✅ | Setelah login user (bukan admin) | Popup/banner login tampil | `test_active_login_banner_is_flashed_after_user_login` |
| A14.3 | ❌ | Login admin | Banner login **tidak** ditampilkan | `test_admin_login_does_not_flash_login_banner` |

---

# BAGIAN B — ADMIN AREA

## B1. Akses & Dashboard Admin

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B1.1 | ✅ | Admin buka halaman user area | Redirect ke panel admin; logout dari area user tetap bisa | `AdminAccessTest` (2 ✅ + 1 ❌ di B1.2) |
| B1.2 | ❌ | User biasa buka area admin | Halaman 403 (forbidden) | `AdminAccessTest::test_a_regular_user_sees_a_forbidden_page_on_admin_area` |
| B1.3 | ✅ | Dashboard admin | Ringkasan operasional tampil (saldo Digiflazz, warna saldo sehat/rendah) | `AdminDashboardTest` (2 test), `DigiflazzBalanceDashboardTest` (3 test) |
| B1.4 | ❌ | User biasa buka dashboard admin | 403 | `AdminDashboardTest::test_regular_users_cannot_access_the_admin_dashboard` |
| B1.5 | ✅ | Sidebar mobile admin | Drawer render | `AdminTablePaginationTest::test_admin_layout_renders_the_mobile_sidebar_drawer` |

## B2. Transaksi / Order (Admin)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B2.1 | ✅ | Lihat daftar transaksi | List tampil; filter by status; butuh `transactions.view` | `AdminTransactionTest::test_admin_can_see_the_transaction_list`, `..._can_be_filtered_by_status` |
| B2.2 | ✅ | Detail transaksi | Invoice tampil; log termasuk stack trace | `test_admin_can_view_the_transaction_invoice`, `..._detail_shows_logs_including_stack_trace` |
| B2.3 | ✅ | **Tandai Lunas** order pending | Order jadi Sukses (settle) | `test_admin_can_mark_a_pending_order_as_paid` |
| B2.4 | ✅ | **Batalkan** order pending | Order expired + **stok dikembalikan** | `test_admin_can_cancel_a_pending_order_and_stock_is_returned` |
| B2.5 | ❌ | Settle order bukan pending | Ditolak (hanya pending) | `test_admin_cannot_settle_an_order_that_is_not_pending` |
| B2.6 | ❌ | Tanpa permission `transactions.update` | 403 untuk settle/expire; invoice butuh `transactions.view` | `test_settle_and_expire_require_the_update_permission`, `test_invoice_requires_the_view_permission` |
| B2.7 | ❌ | User biasa akses halaman transaksi admin | 403 | `test_regular_users_cannot_access_the_transaction_admin_pages` |
| B2.8 | ✅ | Refund order gagal bertipe saldo | Saldo dikredit **sekali**; refund non-saldo hanya menandai status; halaman show render kartu provider + aksi refund | `AdminRefundTest` (✅ 3 test) |
| B2.9 | ❌ | Refund order bukan gagal / tanpa permission | Ditolak | `test_a_non_failed_order_cannot_be_refunded`, `test_a_user_without_update_permission_cannot_refund` |

## B3. Customer Topup (Admin)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B3.1 | ✅ | Lihat list & detail topup | List + filter status + detail lengkap dengan ledger entry | `AdminTopupTest::test_admin_can_see_the_topup_list`, `..._filtered_by_status`, `..._detail_with_its_ledger_entry` |
| B3.2 | ✅ | Settle pending topup (penuh / sebagian) | Kredit sesuai nominal diterima; **tidak double kredit** bila sudah paid | 3 test settle (penuh, partial, idempoten) |
| B3.3 | ✅ | Settle tanpa nominal diterima | Kredit full nominal | `test_settling_without_received_amount_credits_the_full_nominal` |
| B3.4 | ✅ | Batalkan pending topup | Berhasil | `test_admin_can_cancel_a_pending_topup` |
| B3.5 | ❌ | Tanpa `topups.update` | 403 | `test_settling_requires_the_update_permission` |
| B3.6 | ❌ | User biasa akses admin topup | 403 | `test_regular_users_cannot_access_the_topup_admin_pages` |

## B4. Merchandise — Katalog (Admin)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B4.1 | ✅ | Halaman katalog merchandise | 200 untuk user dengan `merchandise.view` | `AdminMerchandiseTest::test_admin_can_view...` (implisit) |
| B4.2 | ✅ | Create merchandise | Tersimpan dengan benar (nama, biaya, stok, aktif) | `test_admin_can_create_merchandise` |
| B4.3 | ✅ | Update merchandise | Field ter-update | `test_admin_can_update_merchandise` |
| B4.4 | ✅ | Delete merchandise | Terhapus | `test_admin_can_delete_merchandise` |
| B4.5 | ✅ | Upload gambar merchandise | Tersimpan di disk (WebP conversion) | `test_admin_can_upload_merchandise_image` |
| B4.6 | ❌ | User biasa akses katalog admin | 403 | `test_regular_users_cannot_access_the_merchandise_admin_page` |
| B4.7 | ❌ | Hanya `merchandise.view` (tanpa update) → create | 403, tidak ada data dibuat | `test_admin_without_update_permission_cannot_create_merchandise` |

## B5. Penukaran Poin (Admin)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B5.1 | ✅ | Lihat daftar penukaran | List render (search + filter status) | `AdminMerchandiseRedemptionTest::test_admin_can_view_redemption_list` |
| B5.2 | ✅ | Tandai Selesai penukaran pending | Status Completed; poin **tidak** berubah (sudah dipotong saat redeem); stok tetap | `test_admin_can_complete_a_pending_redemption` |
| B5.3 | ✅ | Batalkan penukaran pending | Status Cancelled; **poin dikembalikan**; stok +1 | `test_admin_can_cancel_a_pending_redemption_and_refund_points` |
| B5.4 | ❌ | Complete/cancel penukaran bukan pending | Ditolak dengan pesan error, status tak berubah | 2 test `*_non_pending_redemption_is_rejected` |
| B5.5 | ❌ | Tanpa `redemptions.update` (hanya view) → complete | 403 | `test_admin_without_update_permission_cannot_complete_redemption` |
| B5.6 | ❌ | User biasa akses halaman penukaran | 403 | `test_regular_users_cannot_access_redemption_admin` |

## B6. Pengaturan Loyalitas / Tier (Admin)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B6.1 | ✅ | Update threshold Gold & Platinum | Tersimpan; `UserTier::fromPoints()` mengikuti nilai baru | `AdminLoyaltySettingTest::test_admin_can_update_thresholds_and_tiers_follow_them` |
| B6.2 | ✅ | Setelah threshold diubah | Tier user di halaman user langsung berubah | `test_tier_changes_are_reflected_on_user_pages` |
| B6.3 | ❌ | Platinum ≤ Gold | Error validasi `platinum_points` | `test_platinum_must_be_greater_than_gold` |
| B6.4 | ❌ | Threshold ≤ 0 | Error validasi | `test_negative_thresholds_are_rejected` |
| B6.5 | ❌ | Tanpa `loyalty.update` → update | 403 | `test_admin_without_update_permission_cannot_change_thresholds` |
| B6.6 | ❌ | User biasa akses pengaturan loyalitas | 403 | `test_regular_users_cannot_access_loyalty_settings` |

## B7. Banner Login & Dashboard (Admin)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B7.1 | ✅ | CRUD banner login & dashboard | Berhasil (tambah/ubah/hapus) | `AdminBannerTest::test_admin_can_manage_login_banners`, `..._manage_dashboard_banners` |
| B7.2 | ✅ | Upload banner | Gambar dikonversi ke **WebP** | `test_uploaded_banner_image_is_converted_to_webp` |
| B7.3 | ✅ | Search admin | Hasil ter-group; minimal karakter diwajibkan | `test_admin_search_returns_grouped_results`, `..._requires_minimum_characters` |
| B7.4 | ❌ | `ends_at` < `starts_at` | Validasi ditolak | `test_banner_validation_rejects_ends_before_starts` |
| B7.5 | ❌ | User tanpa permission lihat banner | 403 | `test_user_without_permission_cannot_view_banners` |

## B8. Master Brand (Admin)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B8.1 | ✅ | Halaman master brand + upload logo | Berhasil | `AdminBrandTest` (2 test ✅) |
| B8.2 | ❌ | Upload file bukan gambar / tanpa `master-brands.update` | Ditolak (validasi / 403) | `test_a_non_image_upload_is_rejected`, `..._without_update_permission_cannot_upload_a_logo` |
| B8.3 | ✅ | Fetch logo brand otomatis | Domain curated di-download tanpa lookup; domain lain di-resolve dari nama brand; brand tanpa match di-skip; brand ber-logo dibiarkan; import lokal | `FetchBrandLogosTest` (5 test) |

## B9. Master Bank (Admin)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B9.1 | ✅ | Halaman master bank + CRUD | Create/update/delete berhasil | `AdminMasterBankTest` (2 test ✅) |
| B9.2 | ❌ | Nomor rekening duplikat untuk bank sama | Ditolak | `test_a_duplicate_account_number_for_the_same_bank_is_rejected` |
| B9.3 | ❌ | Tanpa permission / user biasa | 403 | `test_a_user_without_permission_cannot_access_master_bank` |

## B10. Bot Digiflazz (Admin)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B10.1 | ✅ | CRUD config (scraper JSON) + halaman sync index/show | Tersimpan sebagai array & render | `BotDigiflazzPagesTest` (5 test) |
| B10.2 | ✅ | Hapus config | Relasi terhapus; response JSON untuk AJAX; form method-spoofed render | `BotDigiflazzConfigDeleteTest` (3 test) |
| B10.3 | ✅ | Job scrape produk | Produk ter-scrape, seller termurah dipilih (pattern filter); tanpa seller → skip/hapus sesuai config; duplikat dihapus | `BotDigiflazzScrapeJobTest` (5 test) |

## B11. Sinkron Produk Digiflazz (Admin)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B11.1 | ✅ | Import produk | Produk + master data (brand/kategori) terderifasi | `DigiflazzProductSyncTest::test_it_imports_products_and_derives_master_data` |
| B11.2 | ✅ | Resync harga | Harga diperbarui, **markup admin tidak ditimpa** | `test_resync_updates_price_without_overwriting_admin_markup` |
| B11.3 | ❌ | Item tanpa SKU | Dilewati | `test_it_skips_items_without_a_sku_code` |

## B12. Fulfillment & Reconcile (Otomatis/Provider)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B12.1 | ✅ | Checkout produk provider | Job dispatch, order → processing; job menandai success + simpan `sn` | `DigiflazzFulfillmentTest` (2 test ✅) |
| B12.2 | ✅ | Webhook signature valid | Order difinalisasi | `test_webhook_validates_the_signature_and_finalizes_the_order` |
| B12.3 | ✅ | VA order pending → dibayar | Job dispatch **sekali** | `test_a_pending_va_order_dispatches_the_job_once_paid` |
| B12.4 | ❌ | Transaksi gagal | Saldo **di-refund** + stok dikembalikan; webhook gagal berulang → refund hanya **sekali** | `test_a_failed_transaction_refunds_balance_and_restores_stock`, `test_a_repeated_failure_webhook_refunds_only_once` |
| B12.5 | ✅ | Reconcile (cron 5 menit) | Order processing lama di-retry & difinalisasi; terbaru dibiarkan | `ReconcileDigiflazzOrdersTest` (3 test) |
| B12.6 | ✅ | Sign & payload Digiflazz | MD5 sign benar; payload cek saldo/topup benar (testing flag); error dikembalikan | `DigiflazzClientTest` (5 test; signature webhook di B12.7) |
| B12.7 | ❌ | Webhook Digiflazz signature salah | `x-hub-signature` gagal → ditolak | `test_verify_webhook_checks_the_x_hub_signature` |

## B13. Auto-Expire (Scheduled Command)

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B13.1 | ✅ | Deposit pending lewat `expires_at` | Menjadi **expired** | `ExpirePendingDepositsTest::test_a_pending_deposit_past_its_expiry_is_expired` |
| B13.2 | ✅ | Order unpaid lewat TTL | Expired + **stok dikembalikan** | `ExpirePendingOrdersTest::test_an_unpaid_order_past_its_ttl_is_expired_and_stock_is_returned` |
| B13.3 | ❌ | Deposit/order masih baru atau sudah paid | Dibiarkan (tidak diexpire) | 3 test `*_left_alone` / `*_never_expired` |
| B13.4 | ❌ | Deposit tanpa `expires_at` | Dilewati | `ExpirePendingDepositsTest::test_a_deposit_without_an_expiry_is_skipped` |

## B14. Tabel Admin & AJAX Pagination

| # | Jenis | Skenario | Ekspektasi | Test |
|---|-------|----------|------------|------|
| B14.1 | ✅ | Tabel users/roles/master bank/brand (ajax) | JSON `rows` + `footer` + modals untuk AJAX | 4 test *_returns_json / *_includes_modals |
| B14.2 | ✅ | Filter search tabel users | Terfilter | `test_users_table_filters_by_search_term` |
| B14.3 | ✅ | Header form server-side | Render | `test_admin_tables_render_the_server_side_header_form` |
| B14.4 | ✅ | Halaman users & roles full view | Render lengkap | `test_users_and_roles_pages_render_the_full_view` |
| B14.5 | ✅ | Showcase komponen | Tetap pakai pencarian client-side | `test_component_showcase_table_still_uses_client_side_search` |

---

# BAGIAN C — RINGKASAN

| Area | Test | Cakupan Utama |
|------|------|---------------|
| Auth (register/login/2FA/reset/verifikasi) | ~20 | Positive & negative alur autentikasi |
| Produk/pencarian/brand/pulsa | ~60 | Filter, search, AJAX, hidden pulsa-data, guard stok |
| Checkout & order user | ~25 | Semua metode bayar + race condition + ownership |
| Deposit/topup + webhook | ~45 | VA/E-Wallet/QRIS, idempotensi kredit, expiry |
| Poin/tier/merchandise | ~30 | Tier threshold, redeem, ledger, CRUD admin |
| Pengaturan & kontak user | ~20 | Validasi dan ownership |
| Notifikasi & push | ~25 | Dropdown, tabs, Beli Lagi, prune token |
| Admin (transaksi/topup/banner/bank/brand) | ~60 | Permission gate, settle/expire, refund, validasi |
| Bot & sync Digiflazz | ~20 | Scrape job, import, webhook signature |
| Scheduled commands | ~7 | Expire deposit/order + reconcile |
| Tabel admin/AJAX | ~10 | JSON rows/footer, sidebar drawer |
| **Total** | **371 test / 1244 assertions** | **Semua hijau (`php artisan test --compact`)** |

**Pola negative test yang konsisten dipakai di seluruh suite:**

1. **Ownership** — user tidak bisa melihat/mengubah data user lain (403/404).
2. **Permission gate** — admin tanpa permission `.view`/`.update` → 403.
3. **State guard** — operasi hanya valid pada status tertentu
   (settle hanya `pending`, refund hanya `failed`, dll).
4. **Idempotensi** — kredit/refund tidak boleh dobel (webhook ulang, settle ulang).
5. **Validasi input** — field wajib, rentang, duplikat, format file.
6. **Race condition** — `lockForUpdate` pada checkout & redeem stok terakhir.
