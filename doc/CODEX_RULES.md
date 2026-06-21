# CODEX RULES

## Mục tiêu

Tất cả code được sinh ra phải tuân thủ các quy tắc dưới đây.

---

# 1. Công nghệ

- Laravel 12
- PHP 8.2+
- MySQL
- Blade Template
- Bootstrap

Không sử dụng:

- VueJS
- React
- Inertia
- Livewire
- AlpineJS

---

# 2. Cấu trúc Controller

Luôn sử dụng:

- Resource Controller

Ví dụ:

- MatHangController
- MauController
- SizeController

Các action:

- index
- create
- store
- edit
- update
- destroy

---

# 3. Validation

Luôn sử dụng Form Request.

Không validate trực tiếp trong Controller.

Ví dụ:

- StoreMatHangRequest
- UpdateMatHangRequest

---

# 4. Database

Luôn sử dụng:

- Migration
- Foreign Key
- Eloquent Relationship

Không sử dụng Query Builder nếu có thể dùng Eloquent.

---

# 5. Model

Mỗi bảng phải có Model riêng.

Ví dụ:

DM_MAT_HANG
→ MatHang

DM_MAU
→ Mau

DM_SIZE
→ Size

---

# 6. Soft Delete

Tất cả dữ liệu nghiệp vụ phải sử dụng:

SoftDeletes

Áp dụng cho:

- Danh mục
- Cắt
- Phân bổ may
- QC
- Nhập kho
- Xuất kho

---

# 7. Giao diện

Sử dụng layout hiện có của dự án.

Không tạo layout mới.

Không sửa cấu trúc layout gốc nếu không cần thiết.

---

# 8. Danh sách dữ liệu

Tất cả màn hình danh sách phải có:

- Tìm kiếm
- Phân trang
- Nút thêm mới
- Nút sửa
- Nút xóa

---

# 9. Form

Các trường bắt buộc phải hiển thị dấu \*

Sử dụng:

- Select2 cho combobox
- Datepicker cho ngày tháng

Nếu project đã có sẵn.

---

# 10. Route

Sử dụng Resource Route.

Ví dụ:

Route::resource('mat-hang', MatHangController::class);

---

# 11. Naming Convention

Controller:

MatHangController

Model:

MatHang

Migration:

create_dm_mat_hang_table

Table:

dm_mat_hang

Foreign Key:

mat_hang_id

---

# 12. Menu

Tất cả menu mới phải được thêm vào menu hiện tại của dự án.

Không tạo menu riêng.

---

# 13. Khi tạo module mới

Luôn tạo đầy đủ:

- Migration
- Model
- Relationship
- Form Request
- Controller
- Route
- Menu
- List View
- Create View
- Edit View

Không tạo thiếu thành phần.

---

# 14. Trước khi sửa code

Luôn đọc:

docs/PROJECT_CONTEXT.md

để hiểu nghiệp vụ của dự án.

Không tự ý thay đổi quy trình nghiệp vụ nếu chưa được yêu cầu.
