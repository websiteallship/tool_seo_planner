# TRACK_DECISIONS — Architecture Decision Records (ADR)

> Ghi lại các quyết định kiến trúc quan trọng, lý do, và trade-offs.

---

## ADR-001: Google Apps Script làm Backend

**Date**: 2026-04-05  
**Status**: Accepted

**Quyết định**: Sử dụng GAS (Google Apps Script) làm backend duy nhất thay vì Node.js, Python, hay serverless cloud.

**Lý do**:
- **Zero cost**: Hoàn toàn miễn phí trong Google Workspace free tier
- **Zero ops**: Không cần server, không cần DevOps, không lo downtime
- **Native integration**: OAuth với Google APIs (Sheets, Drive, GSC, Slides, Docs) sẵn có
- **Đủ cho nhu cầu**: Agency internal tool, không cần auto-scaling

**Trade-offs**:
- Execution timeout 6 phút (giải quyết bằng batch + triggers)
- Không có real-time (polling thay vì WebSocket)
- Khó debug production hơn Node.js
- Không có package manager (không import npm packages)

---

## ADR-002: Google Sheets làm Database

**Date**: 2026-04-05  
**Status**: Accepted

**Quyết định**: Google Sheets là Single Source of Truth — không dùng Firebase, Supabase, hay bất kỳ external DB nào.

**Lý do**:
- **Free tier**: 15GB Drive storage
- **Human-readable**: Planner có thể xem/edit data trực tiếp trong Sheets nếu cần
- **No migration**: Không cần schema migration như SQL
- **Built-in**: SpreadsheetApp API đã tích hợp sẵn trong GAS
- **Backup**: Drive tự backup version history

**Trade-offs**:
- Đọc/ghi chậm hơn DB thực sự → giải quyết bằng batch reads
- Không có transactions → giải quyết bằng LockService
- Quota giới hạn → giải quyết bằng CacheService

---

## ADR-003: SPA-like Architecture với Alpine.js

**Date**: 2026-04-05  
**Status**: Accepted

**Quyết định**: SPA single-page với routing client-side bằng Alpine.js thay vì multi-page GAS (mỗi page = 1 doGet request).

**Lý do**:
- **UX tốt hơn**: Không reload trang khi chuyển module
- **State persistent**: currentProject giữ nguyên khi navigate
- **Nhanh hơn**: Chỉ load HTML shell 1 lần, data fetch async
- **Alpine.js nhẹ**: ~14KB, không cần build step, CDN

**Trade-offs**:
- Routing client-side phức tạp hơn truyền thống
- Phải quản lý state thủ công (không có Redux/Vuex)
- Deep link không hoạt động tự nhiên

---

## ADR-004: Gemini 2.0 Flash cho AI

**Date**: 2026-04-05  
**Status**: Accepted

**Quyết định**: Dùng `gemini-2.0-flash` (free tier) thay vì OpenAI GPT hay các model khác.

**Lý do**:
- **Free**: Free tier đủ cho internal tool (15 RPM, 1M tokens/ngày)
- **Google ecosystem**: Cùng hệ sinh thái GAS → không cần external auth phức tạp
- **JSON output**: `responseMimeType: 'application/json'` native
- **Performance**: Đủ nhanh cho keyword classify, outline generate

**Trade-offs**:
- Rate limit thấp hơn paid tier → giải quyết bằng batch + cache
- Không có streaming (không cần thiết cho use case này)

---

## ADR-005: Per-Project Google Sheet (1 Sheet / Project)

**Date**: 2026-04-05  
**Status**: Accepted

**Quyết định**: Mỗi dự án khách hàng = 1 Google Sheet file riêng biệt, thay vì tất cả trong 1 Spreadsheet.

**Lý do**:
- **Isolation**: 1 project bị lỗi không ảnh hưởng project khác
- **Sharing**: Có thể share sheet với toàn bộ data project cho 1 KH cụ thể
- **Size**: Google Sheets có limit 10M cells — nhiều projects trong 1 file dễ overflow
- **Archive**: Dễ archive/delete project mà không ảnh hưởng Master

**Trade-offs**:
- Cross-project queries phức tạp (nhưng không cần trong use case này)
- Nhiều files hơn trên Drive (có thể folder organize)

---

## ADR-006: AI Review-Before-Apply Pattern

**Date**: 2026-04-05  
**Status**: Accepted

**Quyết định**: Mọi AI output đều phải qua bước human review trước khi apply vào database.

**Lý do**:
- **Accuracy**: AI (kể cả Gemini 2.0) vẫn có thể hallucinate hoặc phân loại sai
- **Trust**: Planner cần kiểm soát data cuối cùng
- **Accountability**: Agency chịu trách nhiệm với output — không thể đổ lỗi cho AI

**Implementation**:
- Mọi AI call → kết quả vào preview panel
- Preview panel có "Edit" trực tiếp trước khi apply
- Button "Áp dụng tất cả" và "Áp dụng đã chọn"
- Không có "Auto-apply on generate"

---

## ADR-007: Shoelace Web Components cho UI

**Date**: 2026-04-05  
**Status**: Accepted

**Quyết định**: Dùng Shoelace 2.x (Web Components) thay vì Bootstrap, DaisyUI, hay custom components.

**Lý do**:
- **No build**: CDN, không cần npm/webpack
- **Accessible**: Built-in ARIA, keyboard navigation
- **Consistent**: Design system nhất quán, có slot system
- **Works với Alpine.js**: Web Components + Alpine.js tương thích tốt

**Trade-offs**:
- Bundle lớn hơn nếu chỉ dùng vài components (nhưng CDN cached)
- Customization khó hơn pure CSS
