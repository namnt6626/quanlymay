# HỆ THỐNG QUẢN TRỊ SẢN XUẤT MAY MẶC

## 1. MỤC TIÊU

Xây dựng hệ thống quản trị sản xuất nội bộ cho xưởng may.

Hệ thống phục vụ:

- Quản lý quá trình cắt
- Quản lý phân bổ may
- Quản lý QC
- Quản lý nhập kho
- Quản lý xuất kho
- Theo dõi tồn kho

Không quản lý:

- Khách hàng
- Đơn hàng bán hàng
- Hợp đồng
- Kế toán
- Công nợ

---

# 2. CÔNG NGHỆ

## Backend

- PHP 8.2+
- Laravel 12

## Frontend

- Blade Template
- Bootstrap

## Database

- MySQL

---

# 3. CẤU TRÚC MENU

Dashboard

Danh mục
├── Mặt hàng
├── Màu sắc
├── Size
├── Bàn cắt
├── Đơn vị cắt
├── Đơn vị may
├── Loại đơn vị may
└── Kho

Sản xuất
├── Cắt
├── Phân bổ may
└── QC

Kho
├── Nhập kho
└── Xuất kho

Hệ thống
├── Người dùng
└── Vai trò

---

# 4. QUY TRÌNH NGHIỆP VỤ

Cắt
↓
Phân bổ may
↓
QC
↓
Nhập kho
↓
Xuất kho

---

# 5. CHI TIẾT NGHIỆP VỤ

## 5.1 Cắt

Là bước khởi tạo dữ liệu sản xuất.

Thông tin:

- Ngày cắt
- Mặt hàng
- Màu sắc
- Size
- Bàn cắt
- Đơn vị cắt
- Số lượng cắt
- Định mức
- Vải tiêu hao
- Ghi chú

Một lần cắt có thể phát sinh nhiều lần phân bổ may.

---

## 5.2 Phân bổ may

Thông tin:

- Ngày phân bổ
- Đơn vị may
- Số lượng giao
- Ghi chú

Một lần cắt có thể giao cho nhiều đơn vị may.

Ví dụ:

Lần cắt 1000 sản phẩm

- Xưởng A: 300
- Xưởng B: 400
- Xưởng C: 300

---

## 5.3 QC

Thông tin:

- Ngày QC
- Số lượng QC
- Số lượng đạt
- Số lượng lỗi
- Số lượng hỏng
- Ghi chú

Một lần phân bổ may có thể phát sinh nhiều lần QC.

---

## 5.4 Nhập kho

Thông tin:

- Kho
- Ngày nhập
- Số lượng nhập
- Ghi chú

Một lần QC có thể nhập kho nhiều lần.

---

## 5.5 Xuất kho

Thông tin:

- Số phiếu
- Ngày xuất
- Ghi chú

Một phiếu xuất có thể chứa nhiều dòng hàng.

Một lần nhập kho có thể được xuất nhiều lần.

---

# 6. QUY TẮC NGHIỆP VỤ

## Cắt

Tổng phân bổ may không được vượt số lượng cắt.

## Phân bổ may

Tổng QC không được vượt số lượng giao may.

## QC

Tổng nhập kho không được vượt số lượng đạt.

## Kho

Tổng xuất kho không được vượt số lượng nhập kho.

## Tồn kho

Tồn kho = Tổng nhập kho - Tổng xuất kho

---

# 7. DATABASE

## DANH MỤC

DM_MAT_HANG

- id
- ma_hang
- ten_hang
- mo_ta
- trang_thai

DM_MAU

- id
- ma_mau
- ten_mau

DM_SIZE

- id
- ma_size
- ten_size

DM_BAN_CAT

- id
- ma_ban
- ten_ban

DM_DON_VI_CAT

- id
- ma_don_vi
- ten_don_vi

DM_LOAI_DON_VI_MAY

- id
- ma_loai
- ten_loai

DM_DON_VI_MAY

- id
- ma_don_vi
- ten_don_vi
- loai_don_vi_id

DM_KHO

- id
- ma_kho
- ten_kho

---

## NGHIỆP VỤ

CAT

- id
- ngay_cat
- mat_hang_id
- mau_id
- size_id
- ban_cat_id
- don_vi_cat_id
- so_luong_cat
- dinh_muc
- vai_tieu_hao
- ghi_chu

PHAN_BO_MAY

- id
- cat_id
- ngay_phan_bo
- don_vi_may_id
- so_luong_giao
- ghi_chu

QC

- id
- phan_bo_may_id
- ngay_qc
- so_luong_qc
- so_luong_dat
- so_luong_loi
- so_luong_hong
- ghi_chu

NHAP_KHO

- id
- qc_id
- kho_id
- ngay_nhap
- so_luong_nhap
- ghi_chu

PHIEU_XUAT_KHO

- id
- so_phieu
- ngay_xuat
- ghi_chu

PHIEU_XUAT_KHO_CHI_TIET

- id
- phieu_xuat_kho_id
- nhap_kho_id
- so_luong_xuat
- ghi_chu

---

# 8. QUAN HỆ DỮ LIỆU

CAT
1 -> N PHAN_BO_MAY

PHAN_BO_MAY
1 -> N QC

QC
1 -> N NHAP_KHO

PHIEU_XUAT_KHO
1 -> N PHIEU_XUAT_KHO_CHI_TIET

NHAP_KHO
1 -> N PHIEU_XUAT_KHO_CHI_TIET

---

# 9. QUY ƯỚC LẬP TRÌNH

- Sử dụng Eloquent Relationship.
- Sử dụng Resource Controller.
- Sử dụng Form Request Validation.
- Sử dụng Migration.
- Sử dụng Seeder cho dữ liệu mẫu.
- Sử dụng Soft Delete cho dữ liệu nghiệp vụ.
- CRUD theo chuẩn Laravel 12.
- Giao diện sử dụng layout hiện có của template.
- Không sử dụng Livewire.
- Không sử dụng Inertia.
- Không sử dụng VueJS.
- Chỉ sử dụng Blade + Bootstrap.

---

# 10. THỨ TỰ PHÁT TRIỂN

1. Menu
2. Danh mục Mặt hàng
3. Danh mục Màu sắc
4. Danh mục Size
5. Danh mục Bàn cắt
6. Danh mục Đơn vị cắt
7. Danh mục Loại đơn vị may
8. Danh mục Đơn vị may
9. Danh mục Kho
10. Cắt
11. Phân bổ may
12. QC
13. Nhập kho
14. Xuất kho
15. Dashboard
16. Người dùng
17. Vai trò
18. Phân quyền
