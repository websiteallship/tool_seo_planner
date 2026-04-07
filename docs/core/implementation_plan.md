# ToolSEO — SEO Planner Management App

## Vai trò & Mục tiêu

**Người dùng**: SEO Planner tại agency, quản lý nhiều dự án SEO cho nhiều website khách hàng.

**Mục tiêu**: Hệ thống quản lý nghiên cứu SEO/GEO — KHÔNG viết content, chỉ lập kế hoạch, cấu trúc silo, keyword mapping, audit checklist, outline mẫu, và export deliverables cho team content (ví dụ: XANH AI).

**Triết lý**: Planning-first, template-driven, checklist-based.

---

## Tech Stack (100% Free, All CDN)

| Layer | Technology | Source |
|:---|:---|:---|
| Database | Google Sheets | Google Workspace |
| Backend | Google Apps Script (V8) | Google |
| Hosting | GAS Web App (HtmlService) | Google |
| Storage | Google Drive | Google (15GB) |
| UI | Shoelace 2.x | CDN jsDelivr |
| CSS | Tailwind CSS 3.x (Play CDN) | CDN |
| Reactivity | Alpine.js 3.x | CDN unpkg |
| Icons | Lucide Icons | CDN unpkg |
| Charts | Chart.js 4.x | CDN jsdelivr |
| API | Google Search Console API v3 | Google Cloud (Free) |

---

## Kiến trúc Hệ thống

### Architecture: SPA-like on GAS

```
┌──────────────────────────────────────────────────────┐
│                   CLIENT (Browser)                    │
│  ┌──────────────────────────────────────────────────┐ │
│  │  index.html (SPA Shell)                          │ │
│  │  ├── Top Bar (project switcher, user)            │ │
│  │  ├── Sidebar Nav (Alpine.js x-data router)       │ │
│  │  ├── Content Area (dynamic page injection)       │ │
│  │  └── Global: modals, toasts, command palette     │ │
│  └──────────────────────────────────────────────────┘ │
│           ↕ google.script.run (async RPC)             │
├──────────────────────────────────────────────────────┤
│                 SERVER (Apps Script)                   │
│  ┌──────────────────────────────────────────────────┐ │
│  │  Code.gs         → doGet(), include()            │ │
│  │  Router.gs       → Page dispatcher               │ │
│  │  API.gs          → CRUD action dispatcher         │ │
│  │  Utils.gs        → ID gen, date, validation       │ │
│  │  SheetDB.gs      → Generic sheet CRUD layer       │ │
│  │  GSCClient.gs    → Google Search Console API      │ │
│  │  services/*.gs   → Business logic per module      │ │
│  └──────────────────────────────────────────────────┘ │
├──────────────────────────────────────────────────────┤
│              DATABASE (Google Sheets)                  │
│                                                       │
│  📋 Master Sheet (app-level)                          │
│  └── _projects (danh sách dự án SEO)                  │
│                                                       │
│  📋 Per-Project Sheet (tạo từ template)               │
│  ├── _config         (project settings)               │
│  ├── keywords        (keyword database)               │
│  ├── silo_structure  (silo/URL hierarchy)             │
│  ├── content_outlines(outline mẫu cho content team)   │
│  ├── technical_audit (audit checklist items)           │
│  ├── backlinks       (link building pipeline)         │
│  ├── rankings        (position tracking snapshots)    │
│  ├── gsc_performance (GSC auto-sync data)             │
│  ├── gsc_index_status(URL inspection results)         │
│  ├── geo_checklist   (GEO readiness per page)         │
│  ├── onpage_checklist(On-page SEO per page)           │
│  ├── rate_card       (단 giá hạng mục)                │
│  └── quotation       (Bảng báo giá dự án)             │
└──────────────────────────────────────────────────────┘
```

### File Structure

```
toolseo/
├── appsscript.json
│
├── Code.gs                       # doGet(), include(), global config
├── Router.gs                     # Page routing
├── API.gs                        # Action dispatcher
├── Utils.gs                      # UUID, date, helpers
├── SheetDB.gs                    # Generic CRUD for any sheet
├── GSCClient.gs                  # Google Search Console API wrapper
├── GeminiClient.gs               # Gemini AI API wrapper (v1beta)
│
├── services/
│   ├── ProjectService.gs         # Multi-project CRUD
│   ├── KeywordService.gs         # Keyword management
│   ├── SiloService.gs            # Silo structure CRUD
│   ├── OutlineService.gs         # Content outline templates
│   ├── AuditService.gs           # Technical audit checklist
│   ├── BacklinkService.gs        # Backlink pipeline
│   ├── RankingService.gs         # Ranking snapshots + GSC sync
│   ├── GSCService.gs             # GSC data processing & scheduling
│   ├── GeoService.gs             # GEO checklist
│   ├── OnPageService.gs          # On-page SEO checklist
│   ├── DashboardService.gs       # Aggregated metrics
│   ├── ReportService.gs          # Auto-generate Google Slides/Docs reports
│   ├── QuotationService.gs       # Báo giá tự động
│   └── AIService.gs              # Gemini AI assistant (keyword, outline, GEO)
│
├── html/
│   ├── index.html                # SPA shell
│   ├── css/
│   │   └── styles.html           # Tailwind overrides + custom CSS
│   ├── js/
│   │   ├── app.html              # Alpine.js app, router, global state
│   │   ├── api-client.html       # google.script.run promise wrapper
│   │   └── components.html       # Reusable Shoelace wrappers
│   └── pages/
│       ├── dashboard.html        # Project dashboard
│       ├── projects.html         # Project list & switcher
│       ├── keywords.html         # Keyword research & clustering
│       ├── silo.html             # Silo architecture builder
│       ├── outlines.html         # Content outline manager
│       ├── audit.html            # Technical audit checklist
│       ├── backlinks.html        # Backlink tracker
│       ├── rankings.html         # Ranking tracker + GSC data
│       ├── gsc.html              # GSC Integration dashboard
│       ├── geo.html              # GEO optimization checklist
│       ├── onpage.html           # On-page SEO checklist
│       ├── reports.html          # Client reporting exporter
│       ├── quotation.html        # Smart pricing engine
│       └── settings.html         # Project & app settings
│
├── templates/                    # Import templates (JSON/CSV)
│   ├── technical_audit_checklist.json
│   ├── geo_checklist.json
│   ├── onpage_checklist.json
│   ├── content_outline_template.json
│   └── silo_template.json
│
└── docs/
    ├── README.md
    └── silo_example.md
```

---

## Modules Chi tiết

### Module 1: Project Management (Multi-site Hub)

**Sheet: `_projects`** (Master)

| Column | Type | Description |
|:---|:---|:---|
| project_id | STRING | UUID |
| client_name | STRING | Tên khách hàng |
| website_name | STRING | Tên website |
| domain | STRING | Domain chính |
| niche | STRING | Ngành nghề |
| target_market | STRING | Thị trường / Vùng (Local SEO) |
| gsc_site_url | STRING | GSC property URL (vd: `sc-domain:example.com`) |
| spreadsheet_id | STRING | Google Sheet ID riêng |
| status | ENUM | active / paused / completed / archived |
| start_date | DATE | Ngày bắt đầu |
| planner | STRING | SEO Planner phụ trách |
| gsc_sync_enabled | BOOLEAN | Bật/tắt auto-sync GSC |
| gsc_last_sync | DATE | Lần sync GSC gần nhất |
| notes | TEXT | Ghi chú |

**UI Features:**
- Card grid hoặc table view cho danh sách project
- Quick-create project (auto-tạo Sheet từ template)
- Project switcher trên top bar
- Status badges (active = green, paused = yellow, etc.)
- Duplicate project (clone settings + template data)

---

### Module 2: Keyword Research & Mapping

**Sheet: `keywords`**

| Column | Type | Description |
|:---|:---|:---|
| keyword_id | STRING | UUID |
| keyword | STRING | Từ khóa |
| search_volume | NUMBER | Volume/tháng |
| difficulty | NUMBER | KD (0-100) |
| cpc | NUMBER | CPC ($) |
| intent | ENUM | informational / commercial / transactional / navigational |
| cluster_group | STRING | Topic cluster name |
| silo_id | STRING | FK → silo_structure |
| target_url | STRING | URL mục tiêu đã map |
| current_position | NUMBER | Vị trí hiện tại |
| priority | ENUM | critical / high / medium / low |
| status | ENUM | researching / mapped / targeting / ranking / paused |
| lsi_keywords | TEXT | LSI/semantic keywords |
| source | STRING | Nguồn (Ahrefs/SEMrush/manual) |
| notes | TEXT | Ghi chú |

**UI Features:**
- **Bulk import**: Paste CSV hoặc upload file từ Ahrefs/SEMrush export
- **Smart table**: Sort, filter, search, pagination
- **Cluster view**: Group keywords by cluster_group (collapsible groups)
- **Intent badges**: Color-coded (info=blue, commercial=orange, transactional=green, nav=gray)
- **Mapping panel**: Assign keyword → URL (dropdown từ silo_structure)
- **Cannibalization detector**: Highlight khi 2+ keywords cùng target_url + cùng intent
- **Stats cards**: Total keywords, mapped %, by intent distribution (pie chart)
- **Export**: CSV download cho team content

---

### Module 3: Silo Architecture & URL Structure

**Sheet: `silo_structure`**

| Column | Type | Description |
|:---|:---|:---|
| silo_id | STRING | UUID |
| silo_name | STRING | Tên Silo / Page |
| silo_type | ENUM | homepage / pillar / cluster / support / blog_category / blog_post |
| parent_id | STRING | ID parent (hierarchy) |
| level | NUMBER | Depth level (0=home, 1=pillar, 2=cluster, 3=support) |
| target_url | STRING | URL slug |
| primary_keyword | STRING | Keyword chính |
| secondary_keywords | TEXT | Keywords phụ (comma-sep) |
| search_intent | ENUM | informational / commercial / transactional |
| h1_tag | STRING | Proposed H1 |
| meta_title | STRING | Proposed meta title |
| meta_description | TEXT | Proposed meta description |
| internal_links_out | TEXT | URLs nội liên kết đi |
| internal_links_in | TEXT | URLs nội liên kết đến |
| word_count_target | NUMBER | Target word count |
| content_type | ENUM | service / category / product / guide / blog / landing |
| schema_types | TEXT | Schema markup required |
| status | ENUM | planned / approved / content_brief_sent / published / optimizing |
| order | NUMBER | Sort order |
| notes | TEXT | Ghi chú |

**UI Features:**
- **Tree view**: Visual hierarchy (indented tree with collapse/expand)
- **Drag-drop reorder**: Kéo thả thay đổi parent/order
- **Silo diagram**: Visual mind-map style (mermaid-like)
- **Quick add**: Thêm node con nhanh
- **Import from template**: Load từ file mẫu (silo_example.md format)
- **Internal link matrix**: Table showing which pages link to which
- **Bulk edit**: Edit meta title/description hàng loạt
- **Export silo map**: Download as PNG/JSON cho client presentation
- **Stats**: Total pages, pages by type, pages by status

---

### Module 4: Content Outline Generator

> **Lưu ý**: Module này KHÔNG viết content. Nó tạo outline/brief chuẩn SEO để export cho team content hoặc tool AI (XANH AI, etc.).

**Sheet: `content_outlines`**

| Column | Type | Description |
|:---|:---|:---|
| outline_id | STRING | UUID |
| silo_id | STRING | FK → silo_structure |
| target_url | STRING | URL mục tiêu |
| working_title | STRING | Tiêu đề dự kiến |
| primary_keyword | STRING | Keyword chính |
| secondary_keywords | TEXT | Keywords phụ |
| search_intent | STRING | Intent |
| target_word_count | NUMBER | Word count mục tiêu |
| meta_title | STRING | Proposed meta title (≤60 chars) |
| meta_description | STRING | Proposed meta desc (≤155 chars) |
| h1_tag | STRING | Proposed H1 |
| outline_structure | TEXT | JSON: array of {heading, level, points, keywords} |
| internal_links | TEXT | URLs cần link đến |
| external_links | TEXT | Sources cần cite |
| schema_required | TEXT | Schema types cần implement |
| eeat_notes | TEXT | E-E-A-T requirements |
| geo_requirements | TEXT | GEO optimization notes |
| tone | STRING | Tone of voice |
| competitor_urls | TEXT | URLs đối thủ tham khảo |
| status | ENUM | draft / reviewed / approved / sent_to_writer / completed |
| assigned_to | STRING | Writer / AI tool |
| notes | TEXT | Additional brief notes |

**outline_structure JSON format:**
```json
[
  {"level": "H2", "heading": "Section Title", "points": ["point 1", "point 2"], "keywords": ["kw1", "kw2"]},
  {"level": "H3", "heading": "Sub-section", "points": ["detail"], "keywords": ["kw3"]}
]
```

**UI Features:**
- **Outline builder**: Visual editor cho heading hierarchy
- **Auto-populate**: Pull data từ silo_structure + keywords khi tạo outline
- **Preview mode**: Render outline dạng readable document
- **Template system**: Load outline template mẫu
- **Export options**:
  - Markdown file (cho XANH AI hoặc writer)
  - JSON (cho integration)
  - Google Doc (auto-create)
- **Status workflow**: draft → reviewed → approved → sent
- **Batch create**: Tạo outline hàng loạt từ silo structure

---

### Module 5: Technical Audit Checklist

**Sheet: `technical_audit`**

| Column | Type | Description |
|:---|:---|:---|
| audit_id | STRING | UUID |
| category | ENUM | crawlability / indexability / performance / mobile / security / schema / architecture |
| item | STRING | Checklist item description |
| severity | ENUM | critical / high / medium / low |
| status | ENUM | not_checked / pass / fail / in_progress / fixed / na |
| url | STRING | URL liên quan (optional) |
| details | TEXT | Findings chi tiết |
| recommendation | TEXT | Đề xuất fix |
| assigned_to | STRING | Người xử lý |
| due_date | DATE | Deadline |
| fixed_date | DATE | Ngày fix xong |
| evidence | STRING | Link screenshot/URL minh chứng |
| notes | TEXT | Ghi chú |

**Pre-built Checklist Template (55+ items):**

```
📁 Crawlability (8 items)
├── robots.txt validation
├── XML sitemap accuracy & submission
├── Crawl budget optimization
├── No crawl traps (infinite pagination, faceted nav)
├── Orphaned pages check
├── Redirect chains & loops
├── Internal linking depth (≤3 clicks)
└── Log file analysis (bot crawl patterns)

📁 Indexability (7 items)
├── Canonical tag implementation
├── Meta robots directives audit
├── Duplicate content consolidation
├── Redirect (301 vs 302) audit
├── Status codes (4xx, 5xx, soft 404)
├── Index coverage in GSC
└── Pagination handling (rel=next/prev or load more)

📁 Performance / Core Web Vitals (8 items)
├── LCP < 2.5s
├── INP < 200ms
├── CLS < 0.1
├── Image optimization (WebP/AVIF, lazy load)
├── CSS/JS minification
├── Gzip/Brotli compression
├── Render-blocking resources
└── Server response time (TTFB < 600ms)

📁 Mobile Experience (5 items)
├── Mobile-first indexing readiness
├── Viewport meta tag
├── Tap targets (≥48px)
├── Font size readability
└── Content parity (mobile = desktop)

📁 Security (4 items)
├── HTTPS everywhere
├── Mixed content warnings
├── HSTS header
└── Malware/hacked content scan

📁 Schema & Structured Data (6 items)
├── JSON-LD implementation
├── Organization schema
├── Article/BlogPosting schema
├── FAQ schema
├── Product schema (if applicable)
└── Rich Results Test validation

📁 Architecture & Navigation (6 items)
├── Breadcrumb implementation
├── URL structure (clean, descriptive)
├── Header/footer navigation
├── Internal search functionality
├── 404 custom page
└── Sitemap HTML (user-facing)

📁 International (3 items) — if applicable
├── Hreflang tags
├── x-default implementation
└── Language/region targeting in GSC

📁 AI / GEO Readiness (8 items)
├── AI crawler access (GPTBot, CCBot, Google-Extended)
├── Content extractability (modular sections)
├── Answer-first formatting
├── Entity clarity (brand mentions)
├── Fact density (stats, data, citations)
├── Author/expertise signals
├── Freshness timestamps
└── TL;DR / Key Takeaway blocks
```

**UI Features:**
- **Import template**: One-click load full checklist
- **Progress dashboard**: % complete by category, severity breakdown
- **Filter/group**: By category, severity, status
- **Bulk update status**: Check/uncheck multiple items
- **Assign & schedule**: Assign items to team members with due dates
- **Evidence attachments**: Link to screenshots, PageSpeed reports
- **Re-audit**: Reset checklist for periodic re-audits
- **Export**: PDF/Markdown audit report

---

### Module 6: On-Page SEO Checklist (Per Page)

**Sheet: `onpage_checklist`**

| Column | Type | Description |
|:---|:---|:---|
| check_id | STRING | UUID |
| url | STRING | URL trang |
| silo_id | STRING | FK → silo_structure |
| category | ENUM | title / meta / headings / content / images / links / schema / ux |
| item | STRING | Checklist item |
| status | ENUM | not_checked / pass / fail / na |
| current_value | TEXT | Giá trị hiện tại |
| recommended_value | TEXT | Giá trị đề xuất |
| notes | TEXT | Ghi chú |

**Pre-built On-Page Checklist (per page, ~30 items):**

```
📁 Title & Meta (5 items)
├── Title tag ≤ 60 chars, contains primary KW
├── Meta description ≤ 155 chars, has CTA
├── URL slug clean, contains KW
├── Title unique (not duplicate with other pages)
└── Meta description unique

📁 Headings (4 items)
├── Exactly one H1, contains primary KW
├── H2-H3 hierarchy logical (no skipping)
├── H2s contain secondary/LSI keywords
└── Question-style headings (for featured snippets)

📁 Content Quality (6 items)
├── Primary KW in first 100 words
├── Word count meets target
├── No thin content (>300 words min)
├── E-E-A-T signals present
├── Content matches search intent
└── No keyword stuffing (natural density)

📁 Images (4 items)
├── All images have descriptive alt text
├── Image filenames are descriptive
├── Images compressed (WebP preferred)
└── Lazy loading implemented

📁 Internal Links (4 items)
├── Links to pillar/parent page
├── Links from pillar/parent page
├── Descriptive anchor text (not "click here")
└── No broken internal links

📁 Schema (3 items)
├── Relevant schema implemented
├── JSON-LD format
└── Validated with Rich Results Test

📁 UX Signals (4 items)
├── Above-the-fold content compelling
├── CTA visible and clear
├── Mobile render correct
└── Page load < 3s
```

**UI Features:**
- **Page selector**: Dropdown chọn URL từ silo_structure
- **Auto-generate**: Tạo checklist cho tất cả pages trong silo
- **Score card**: On-page SEO score per page (pass/total items)
- **Comparison view**: So sánh score nhiều pages cùng lúc
- **Bulk actions**: Mark multiple items
- **Export**: Per-page report cho devs/writers

---

### Module 7: GEO Optimization Checklist

**Sheet: `geo_checklist`**

| Column | Type | Description |
|:---|:---|:---|
| geo_id | STRING | UUID |
| url | STRING | URL trang |
| silo_id | STRING | FK → silo_structure |
| category | ENUM | technical / content_structure / authority / measurement |
| item | STRING | Checklist item |
| status | ENUM | not_checked / pass / fail / in_progress / na |
| details | TEXT | Chi tiết findings |
| notes | TEXT | Ghi chú |

**Pre-built GEO Checklist (~25 items):**

```
📁 Technical Accessibility (5 items)
├── robots.txt allows GPTBot
├── robots.txt allows CCBot
├── robots.txt allows Google-Extended
├── Schema markup (JSON-LD) comprehensive
└── Semantic HTML hierarchy (article, section, ul/ol)

📁 Content Structure — Answer-First (8 items)
├── Direct answer in first 75-120 words
├── Question-style H2 headings
├── Modular content blocks (75-300 words per section)
├── TL;DR / Key Takeaways section
├── Plain language (no jargon without definition)
├── Tables/lists for data presentation
├── Fact-dense content (stats, numbers, citations)
└── Original data/insights included

📁 Authority & E-E-A-T (6 items)
├── Author bio with credentials visible
├── About page linked
├── Citations to authoritative sources
├── Brand name consistently mentioned
├── Content updated within 30 days
└── Freshness timestamp visible

📁 Off-Page & Measurement (6 items)
├── Brand mentions on high-authority platforms
├── Cross-channel messaging consistency
├── AI visibility monitoring (ChatGPT/Gemini/Perplexity)
├── Prompt testing (20-30 queries tested)
├── Share of AI voice tracked
└── AI citation sentiment monitored
```

**UI Features:**
- **Per-page GEO score**: Radar chart (4 categories)
- **AI Visibility log**: Track manual citation checks
- **Prompt testing log**: Record queries & results from AI platforms
- **Batch assessment**: Run GEO check cho toàn bộ silo
- **Export**: GEO readiness report

---

### Module 8: Backlink Tracker

**Sheet: `backlinks`**

| Column | Type | Description |
|:---|:---|:---|
| backlink_id | STRING | UUID |
| referring_domain | STRING | Domain nguồn |
| referring_url | STRING | URL nguồn |
| target_url | STRING | URL đích |
| anchor_text | STRING | Anchor text |
| link_type | ENUM | dofollow / nofollow / ugc / sponsored |
| domain_authority | NUMBER | DA/DR |
| status | ENUM | prospect / contacted / negotiating / live / lost / rejected |
| outreach_date | DATE | Ngày liên hệ |
| live_date | DATE | Ngày link live |
| contact_info | STRING | Email / contact |
| cost | NUMBER | Chi phí (VND/USD) |
| campaign | STRING | Tên campaign link building |
| notes | TEXT | Ghi chú |

**UI Features:**
- **Pipeline view**: Kanban (prospect → contacted → negotiating → live)
- **Bulk import**: CSV từ Ahrefs/SEMrush backlink export
- **Stats cards**: Total live links, avg DA, dofollow ratio
- **Anchor text distribution**: Pie chart
- **Domain authority histogram**: Bar chart
- **Lost link alerts**: Highlight khi status chuyển → lost
- **Campaign grouping**: Group by campaign
- **Export**: Backlink report

---

### Module 9: Ranking Tracker (GSC Auto-Sync + Manual)

**Sheet: `rankings`**

| Column | Type | Description |
|:---|:---|:---|
| tracking_id | STRING | UUID |
| keyword | STRING | Từ khóa tracked |
| target_url | STRING | URL mục tiêu |
| position | NUMBER | Avg. Position (từ GSC hoặc manual) |
| previous_position | NUMBER | Vị trí check trước |
| change | NUMBER | +/- thay đổi |
| best_position | NUMBER | Best ever |
| clicks | NUMBER | Clicks (từ GSC) |
| impressions | NUMBER | Impressions (từ GSC) |
| ctr | NUMBER | CTR % (từ GSC) |
| search_engine | ENUM | google / bing |
| device | ENUM | desktop / mobile |
| country | STRING | Country code (từ GSC) |
| tracked_date | DATE | Ngày check |
| data_source | ENUM | gsc_auto / manual / csv_import |
| featured_snippet | BOOLEAN | Có FS? |
| ai_overview | BOOLEAN | Có AI Overview? |
| serp_features | TEXT | Các SERP features khác |
| notes | TEXT | Ghi chú |

**Data Sources:**
- **GSC Auto-Sync**: Tự động pull từ `searchAnalytics.query` API hàng ngày (trigger)
- **Manual input**: Nhập tay cho keywords chưa có data GSC
- **CSV import**: Từ Ahrefs/SEMrush ranking exports

**UI Features:**
- **Sync status bar**: Hiển thị GSC sync status, last sync time, next scheduled
- **🔄 Sync Now button**: Manual trigger GSC data pull
- **Bulk update**: Paste ranking data hàng loạt (date snapshot)
- **Trend chart**: Line chart tracking position + clicks over time per keyword
- **Distribution chart**: Pie (Top 3, 4-10, 11-20, 21-50, 50+)
- **Movers**: Top gainers / losers per period
- **Traffic impact**: Clicks & Impressions trend alongside position changes
- **SERP features tracking**: FS, AI Overview icons
- **Compare snapshots**: So sánh 2 dates
- **Data source filter**: Filter by gsc_auto / manual / csv_import
- **Import**: CSV từ ranking tool exports
- **Export**: Ranking progress report

---

### Module 10: Google Search Console Integration

> **Tính năng cốt lõi**: Tự động kéo data từ GSC để enriching tất cả modules khác. Là "data engine" của toàn bộ app.

#### 10.1 Setup & Authentication

**Yêu cầu:**
- Google Cloud Project (GCP) linked với GAS project
- Enable "Google Search Console API" trong GCP
- OAuth scopes trong `appsscript.json`:
```json
"oauthScopes": [
  "https://www.googleapis.com/auth/spreadsheets",
  "https://www.googleapis.com/auth/script.external_request",
  "https://www.googleapis.com/auth/webmasters.readonly"
]
```
- User phải có quyền truy cập GSC property của website

#### 10.2 GSC Performance Data (searchAnalytics.query)

**Sheet: `gsc_performance`**

| Column | Type | Description |
|:---|:---|:---|
| record_id | STRING | UUID |
| query | STRING | Search query (keyword) |
| page | STRING | URL trang |
| clicks | NUMBER | Số click |
| impressions | NUMBER | Số hiển thị |
| ctr | NUMBER | Click-through rate (%) |
| position | NUMBER | Vị trí trung bình |
| country | STRING | Mã quốc gia |
| device | ENUM | desktop / mobile / tablet |
| date | DATE | Ngày |
| search_appearance | STRING | SERP feature type |
| synced_at | DATETIME | Thời điểm sync |

**API Details:**
- **Endpoint**: `POST https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/searchAnalytics/query`
- **Dimensions**: query, page, country, device, date, searchAppearance
- **Filters**: by query (contains/equals), by page, by country, by device
- **Limits**: 25,000 rows/request (pagination supported), data delay 2-3 ngày
- **Retention**: 16 tháng dữ liệu

**Tích hợp vào modules khác:**
- → **Keywords**: Auto-enrich clicks, impressions, CTR, position cho keywords đã mapped
- → **Rankings**: Auto-populate daily position snapshots
- → **Dashboard**: Total clicks, impressions, avg position KPIs
- → **Silo**: Per-page performance metrics

#### 10.3 URL Inspection API

**Sheet: `gsc_index_status`**

| Column | Type | Description |
|:---|:---|:---|
| inspection_id | STRING | UUID |
| url | STRING | URL được inspect |
| verdict | ENUM | PASS / NEUTRAL / FAIL / VERDICT_UNSPECIFIED |
| coverage_state | STRING | Trạng thái index (Submitted and indexed, Crawled - currently not indexed, etc.) |
| robotstxt_state | ENUM | ALLOWED / DISALLOWED |
| indexing_state | ENUM | INDEXING_ALLOWED / BLOCKED_BY_META_TAG / BLOCKED_BY_HTTP_HEADER |
| page_fetch_state | STRING | Fetch status (SUCCESSFUL, SOFT_404, etc.) |
| crawled_as | ENUM | DESKTOP / MOBILE |
| canonical_url | STRING | Google-selected canonical |
| user_canonical | STRING | User-declared canonical |
| mobile_usability | ENUM | MOBILE_FRIENDLY / NOT_MOBILE_FRIENDLY |
| mobile_issues | TEXT | Danh sách lỗi mobile (JSON) |
| rich_results | TEXT | Rich results detected (JSON) |
| last_crawl_time | DATETIME | Lần crawl gần nhất |
| inspected_at | DATETIME | Thời điểm inspect |

**API Details:**
- **Endpoint**: `POST https://searchconsole.googleapis.com/v1/urlInspection/index:inspect`
- **Quota**: 2,000 requests/ngày/property, 600 requests/phút
- **Chiến lược**: Batch inspect theo priority (pillar pages trước, support pages sau)

**Tích hợp vào modules khác:**
- → **Technical Audit**: Auto-fill indexing status, canonical issues, mobile usability
- → **On-Page Checklist**: Auto-check mobile friendliness, index status
- → **Silo**: Show index badge (✅/❌) per page

#### 10.4 Sitemaps API

**Tích hợp vào Technical Audit:**
- **Endpoint**: `GET https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/sitemaps`
- Auto-check: Sitemap submitted? Last read date? URLs discovered vs indexed?
- Auto-fill audit checklist items cho Crawlability category

#### 10.5 Scheduling & Triggers

| Trigger | Frequency | Action |
|:---|:---|:---|
| Performance Sync | Daily (2:00 AM) | Pull last 3 days data from searchAnalytics |
| URL Inspection | Weekly (Sunday) | Batch inspect top 100 priority URLs |
| Sitemap Check | Weekly (Monday) | Check sitemap status & errors |
| Rankings Update | Daily (after perf sync) | Compute position changes, update rankings sheet |

**UI Features (GSC Dashboard Page):**
- **Connection status**: GSC property linked ✅ / not linked ❌
- **Sync controls**: Manual sync button, sync history log
- **Performance overview**: Clicks, Impressions, CTR, Position (last 28 days)
- **Performance trend**: Line chart (clicks + impressions over time)
- **Top queries table**: Sortable by clicks, impressions, position, CTR
- **Top pages table**: Same metrics aggregated by page
- **Index coverage summary**: Indexed / Excluded / Error counts
- **URL Inspection tool**: Input URL → inspect → show results inline
- **Sitemap status**: List sitemaps with status badges
- **Device breakdown**: Desktop vs Mobile performance (bar chart)
- **Country breakdown**: Performance by country (table)

---

### Module 11: Dashboard (Project Overview)

**Features:**
- **KPI Cards** (per project):
  - 📈 Total Clicks (last 28 days, from GSC)
  - 👁️ Total Impressions (last 28 days, from GSC)
  - 📍 Avg Position (from GSC)
  - 🔑 Total keywords (mapped / unmapped)
  - 🏗️ Silo completeness (pages planned / published)
  - 📝 Outlines created / sent
  - 🔍 Technical audit score (% pass)
  - ✅ On-page SEO avg score
  - 🤖 GEO readiness avg score
  - 🔗 Backlinks (live count, avg DA)
  - 📊 Ranking distribution (Top 10 count)

- **Charts** (Chart.js 4.x):
  - GSC Performance trend (clicks + impressions, line, 28 days)
  - Keyword intent distribution (doughnut)
  - Silo structure completeness (horizontal bar)
  - Ranking position trend (line, last 12 snapshots)
  - Audit issues by severity (stacked bar)
  - GEO readiness radar
  - Backlink growth (line)
  - Device split: Desktop vs Mobile traffic (bar)

- **Quick Actions**:
  - "🔄 Sync GSC Data" (manual trigger)
  - "Run full technical audit" (load checklist template)
  - "Generate outlines for unmapped silos"
  - "Export project summary"

---

### Module 12: Client Reporting System (Export Tool)

> **Mục tiêu**: Tự động hóa quá trình làm báo cáo định kỳ cho khách hàng agency, biến raw metrics thành insight kinh doanh có cấu trúc thông qua Google Slides và Google Docs.

#### 12.1 Report Templates (Lưu trên Google Drive)

**1. Monthly SEO Report (Google Slides):**
- **Sử dụng cho**: Báo cáo tổng quan hàng tháng (executive presentation).
- **Cấu trúc Slide Template**:
  - `{{client_name}}` | SEO Monthly Report | `{{report_month}}`
  - Executive Summary (Key wins, Challenges, Next focus)
  - Traffic & Visibility: Chart nhúng từ Sheets + `{{total_clicks}}`, `{{avg_position}}`
  - Keyword Rankings: Bảng top keyword thay đổi
  - Silo Progress: `{{published_pages}}`/`{{total_pages}}`
  - Work Completed: Bullet points công việc đã làm
  - Next 30 Days Plan: Công việc dự kiến

**2. Project Status Update (Google Docs):**
- **Sử dụng cho**: Báo cáo tiến độ chi tiết 2 tuần/lần.
- **Cấu trúc Doc Template**: Cập nhật tiến độ Silo, outline status, technical audit fixes list.

#### 12.2 Report Generator Engine (GAS)

**Quy trình Hoạt động:**
1. User chọn Project và chọn loại Report (Monthly Slides / Status Doc).
2. Code copy template gốc (`makeCopy()`) tạo file mới trong thư mục dự án trên Drive.
3. Replace Data (`replaceAllText()`): Thay `{{placeholder}}` bằng data kéo từ `DashboardService` và `GSCService`.
4. Embed Charts (`insertSheetsChartAsImage()`): Render biểu đồ xu hướng (từ GSC) thành hình ảnh và chèn vào placeholder image trên Slide.
5. Return URL: Trả về link Google Slide/Doc đã copy cho Planner.

**Bảng: `_reports` (Lưu lịch sử báo cáo)**
| Column | Type | Description |
|:---|:---|:---|
| report_id | STRING | UUID |
| report_type | ENUM | monthly_slides / status_doc / strategy_deck |
| generated_date| DATE | Ngày xuất |
| drive_url | STRING | Link file Google Drive |
| status | ENUM | draft / sent_to_client |

**UI Features (Reports Page):**
- **Template Manager**: Nơi nhập ID của file Google Slide/Doc làm gốc (template ID).
- **One-click Generate**: Nút bấm render báo cáo tự động cho project hiện tại.
- **Custom Input Fields**: Form nhập liệu cho các mục subjective (như `Executive Summary`, `Challenges`, `Plan for next month`) trước khi render.
- **Report History**: Danh sách link Docs/Slides đã xuất.

---

### Module 13: Quotation & Pricing Engine (Bảo Giá Tự Động)

> **Mục tiêu**: Tự động tính toán và xuất file báo giá (Google Sheets/PDF) cho khách hàng dựa trên cấu trúc Silo tạo ra từ Module 3.

#### 13.1 Cấu trúc Sheet
**1. Sheet `rate_card` (Bảng giá gốc):**
| Column | Type | VD Cấu hình |
|:---|:---|:---|
| item_category | ENUM | `content`, `backlink`, `audit`, `fee` |
| item_type | STRING | `blog_1000w`, `service_page`, `guest_post_DA40` |
| tier | ENUM | `standard`, `expert` |
| unit_price | NUMBER | 500000 |

**2. Sheet `quotation` (Chi tiết báo giá):**
| Column | Type | Description |
|:---|:---|:---|
| item_id | STRING | UUID |
| item_group | STRING | Nhóm (vd: Viết Content) |
| item_name | STRING | Tên hạng mục (Vd: Bài blog 1000 chữ) |
| base_data_id | STRING | ID liên kết Silo/Task (để track) |
| quantity | NUMBER | Số lượng |
| unit_price | NUMBER | Đơn giá |
| subtotal | NUMBER | Thành tiền |
| notes | TEXT | Ghi chú |

#### 13.2 Logic Hoạt động (UX Flow)
1. Planner chốt mảng **Silo Structure**.
2. Click **Generate Quotation**. Script quét `silo_structure`, group các pages theo `content_type` và `word_count_target`.
3. Lookup đơn giá từ `rate_card` ghép vào cấu hình gom nhóm.
4. Render bảng báo giá ra giao diện tương tác: cho phép Planner sửa tay đơn giá, thêm mục giảm giá, chiết khấu.
5. Export: Auto-clone file "Google Sheet Quotation Template" (chuẩn form agency, có Cover, Terms & Conditions) và điền data → trả link báo giá hoàn chỉnh cho Planner.

---

### Module 14: Gemini AI Assistant

> **Vị trí**: AI là "copilot" của SEO Planner trong ToolSEO, **KHÔNG thay thế Planner** mà tăng tốc từng bước trong workflow. Tất cả gần như 0 chi phí với Gemini Flash free tier.

#### 14.1 GAS Integration Setup

**`GeminiClient.gs`** — Wrapper dùng chung cho mọi module:
```javascript
// API key lưu trong Script Properties (không hardcode)
const GEMINI_API_KEY = PropertiesService.getScriptProperties().getProperty('GEMINI_API_KEY');
const GEMINI_MODEL   = 'gemini-2.0-flash'; // Free tier, ~1500 req/ngày
const GEMINI_URL     = `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent?key=${GEMINI_API_KEY}`;

function callGemini(prompt, responseSchema = null) {
  const payload = {
    contents: [{ parts: [{ text: prompt }] }],
    generationConfig: responseSchema
      ? { responseMimeType: 'application/json', responseSchema }
      : { temperature: 0.3, maxOutputTokens: 8192 }
  };
  const res = UrlFetchApp.fetch(GEMINI_URL, {
    method: 'post', contentType: 'application/json',
    payload: JSON.stringify(payload), muteHttpExceptions: true
  });
  const data = JSON.parse(res.getContentText());
  const text = data.candidates?.[0]?.content?.parts?.[0]?.text ?? '';
  return responseSchema ? JSON.parse(text) : text;
}
```

#### 14.2 Tính năng AI theo Từng Module

---

##### AI Feature 1: Keyword Intent Classifier (Module 2)

**Trigger**: User paste 1 list keywords thô (chưa có intent) → click **"AI Classify Intent"**

**Input**: Danh sách keyword raw

**Output** (JSON structured):
```json
[
  { "keyword": "thiết kế nội thất nha trang", "intent": "commercial", "confidence": 0.92 },
  { "keyword": "nội thất là gì", "intent": "informational", "confidence": 0.97 }
]
```

**Ưuới**: Tự động gán intent badge + color code cho toàn bộ keyword list trong 1 request

---

##### AI Feature 2: Keyword Cluster Generator (Module 2)

**Trigger**: Chọn 50-200 keywords → click **"AI Cluster"**

**Output** (JSON):
```json
[
  {
    "cluster_name": "Dịch vụ thiết kế nội thất",
    "pillar_keyword": "thiết kế nội thất nha trang",
    "keywords": ["thiết kế nội thất trọn gói", "công ty nội thất nha trang"],
    "suggested_silo_level": "pillar",
    "content_type": "service"
  }
]
```

**Ưuới**: Tự động gợi ý `cluster_group` + silo mapping, giảm 70% thời gian cluster thủ công

---

##### AI Feature 3: Silo Structure Suggester (Module 3)

**Trigger**: Nhập domain + ngành nghề + danh sách keyword clusters → click **"AI Generate Silo"**

**Output** (JSON):
```json
[
  { "level": 0, "name": "Homepage", "keyword": "thiết kế nội thất nha trang", "type": "homepage" },
  { "level": 1, "name": "Thiết Kế Nội Thất", "keyword": "...", "type": "pillar", "parent": 0 },
  { "level": 2, "name": "Thiết kế phòng khách", "keyword": "...", "type": "cluster", "parent": 1 }
]
```

**Ưuới**: Khởi tạo silo từ con số 0 cho dự án mới trong 30 giây thay vì 2-3 tiếng

---

##### AI Feature 4: Content Outline Auto-Generator (Module 4)

**Trigger**: Chọn 1 node trong Silo → click **"AI Generate Outline"**

**Input**: primary_keyword, content_type, word_count_target, secondary_keywords

**Output** (JSON structured — inject thẳng vào `outline_structure` field):
```json
{
  "meta_title": "Thiết Kế Nội Thất Nha Trang | Chính Sách Ờ Quyền",
  "meta_description": "Mô tả ≤ 155 ký tự...",
  "h1": "Thiết Kế Nội Thất Nha Trang — Từ Bản Vẽ Đến Hiện Thực",
  "outline_structure": [
    {
      "level": "H2",
      "heading": "Tại Sao Chọn Thiết Kế Nội Thất Trọn Gói?",
      "points": ["Tiết kiệm 30% thời gian", "1 đầu mối linh suất"],
      "keywords": ["nội thất trọn gói", "thiết kế và thi công"]
    }
  ],
  "faq_suggestions": [
    "Chi phí thiết kế nội thất Nha Trang bao nhiêu?",
    "Thời gian thiết kế mất bao lâu?"
  ],
  "eeat_notes": "Nên đề cập số dự án, năm kinh nghiệm, testimonial khách hàng",
  "schema_types": ["Service", "FAQPage", "LocalBusiness"],
  "word_count_estimate": 1500
}
```

**Ưuới**: Outline chuẩn SEO trong &lt;10 giây. Planner chỉ cần review + tinh chỉnh

---

##### AI Feature 5: LSI & Semantic Keyword Expander (Module 2)

**Trigger**: Chọn 1 keyword → click **"AI Expand LSI"**

**Output** (JSON):
```json
{
  "primary": "thiết kế nội thất nha trang",
  "lsi_keywords": ["công ty nội thất", "bản vẽ nội thất", "vật liệu nội thất"],
  "semantic_entities": ["Kánh Hòa", "Nha Trang", "TCXDVN", "kiến trúc sư"],
  "people_also_ask": [
    "Thiết kế nội thất tốn bao nhiêu tiền?",
    "Nên chọn công ty nội thất theo tiêu chí nào?"
  ],
  "related_queries": ["nội thất trọn gói nha trang", "phòng ngủ đẹp nha trang"]
}
```

**Ưuới**: Enrich keyword record, auto-populate `lsi_keywords` field trong sheet

---

##### AI Feature 6: GEO Content Scoring Assistant (Module 7)

**Trigger**: Nhập/paste nội dung đoạn văn → click **"AI GEO Check"**

**Output** (JSON):
```json
{
  "geo_score": 72,
  "issues": [
    { "rule": "Direct answer in first 75 words", "status": "fail", "fix": "Move the direct answer to paragraph 1" },
    { "rule": "Modular content blocks", "status": "pass" },
    { "rule": "Fact density", "status": "warning", "fix": "Add at least 2 specific numbers/statistics" }
  ],
  "improvement_suggestions": [
    "Add a TL;DR box after the introduction",
    "Use question-style H2 headings for better AI citation probability"
  ]
}
```

**Ưuới**: Real-time GEO scoring thay vì kiểm tra thủ công từng mục

---

##### AI Feature 7: Audit Recommendation Generator (Module 5)

**Trigger**: Chọn 1 audit item có status "fail" → click **"AI Recommend Fix"**

**Input**: `category`, `item`, `details` (findings)

**Output** (text):
> "Dựa trên findings: `Trang có 2 thẻ canonical trỏi chiếu`. Đề xuất: Kiểm tra header HTTP X-Canonical và thẻ `<link rel="canonical">` trong HTML. Giữ lại chỉ 1 source. Ưu tiên xử lý trong 48h vì ảnh hưởng trực tiếp index."

**Ưuới**: Planner không cần phải là technical SEO expert — AI explain + recommend action

---

##### AI Feature 8: Monthly Report Executive Summary (Module 12)

**Trigger**: Khi generate report → AI tự động viết phần Executive Summary

**Input**: GSC data (clicks MoM, position, top keywords thay đổi), work done log

**Output** (text cho slide):
> "✅ Traffic organic tăng 23% so với tháng trước (1,234 → 1,519 clicks). Vị trí trung bình cải thiện từ 18.4 → 15.1. Đã hoàn thiện 8 outline và publish 3 trang Pillar. ⚠️ Cần ưu tiên xử lý 4 lỗi technical còn tồn đọảng và push thêm 2 backlink DA40+ trong tháng tới."

**Ưuới**: Tiết kiệm 30 phút viết executive summary mỗi báo cáo hàng tháng

---

#### 14.3 Bảng tổng hợp AI features

| AI Feature | Module | Trigger | Output type | Ưuới chính |
|:---|:---|:---|:---|:---|
| Intent Classifier | M2 Keywords | Bulk import | Structured JSON | Tự gán intent 200+ keywords |
| Keyword Clusterer | M2 Keywords | Select list | Structured JSON | Tạo cluster groups + silo suggestions |
| Silo Suggester | M3 Silo | New project | Structured JSON | Khởi tạo toàn bộ silo từ scratch |
| Outline Generator | M4 Outline | Per silo node | Structured JSON | H2/H3 + meta + FAQ + E-E-A-T notes |
| LSI Expander | M2 Keywords | Per keyword | Structured JSON | LSI, entities, PAA questions |
| GEO Scorer | M7 GEO | Paste content | Structured JSON | GEO compliance score + fix suggestions |
| Audit Recommender | M5 Audit | Per fail item | Text | Giải thích + actionable fix |
| Report Summarizer | M12 Reports | Report gen | Text | Executive summary tự động |

#### 14.4 Setup & Config

**`GeminiClient.gs` — API config:**
- API Key: Lưu trong **Script Properties** (`GEMINI_API_KEY`)
- Model: `gemini-2.0-flash` (free tier ~1500 req/ngày, đủ dùng cho internal tool)
- Structured output: Sử dụng `responseMimeType: 'application/json'` + `responseSchema` để luôn nhận đúcng format
- Rate limiting: Giới hạn 1 call/module/action để tránh abuse
- Fallback: Nếu API fail → show input form cho user nhập thủ công

**UI pattern cho mọi AI button:**
```
[✨ AI Suggest Intent]  ← nút secondary, không phải primary
  → Click → Loading spinner ("AI đang phân tích...")
  → Result hiện bên dưới dạng preview có thể edit
  → [Apply] / [Dismiss]
```

**Nhẫn mạnh**: AI suggestions luôn là **proposal**, user luôn có quyền review + sửa trước khi apply.

---

## Template System (Import JSON)

Các template pre-built, user import 1-click vào project:

### `templates/technical_audit_checklist.json`
55+ items grouped by 9 categories (xem Module 5)

### `templates/geo_checklist.json`
25+ items grouped by 4 categories (xem Module 7)

### `templates/onpage_checklist.json`
30+ items grouped by 7 categories (xem Module 6)

### `templates/content_outline_template.json`
Standard outline structure with common H2/H3 patterns per content type:
- Service page outline
- Blog/guide article outline
- Product category outline
- Landing page outline
- FAQ page outline

### `templates/silo_template.json`
Sample silo structures by industry:
- Construction & Interior (từ silo_example.md)
- E-commerce
- SaaS
- Local Business
- Agency/Portfolio

---

## UI Design Direction

### Layout
```
┌─────────────────────────────────────────────────────────┐
│ ⚡ ToolSEO          [Project: XYZ Corp ▾]    [👤 User] │
├────────┬────────────────────────────────────────────────┤
│        │                                                │
│  📊    │   Dashboard / Content Area                     │
│  🔑    │                                                │
│  🏗️    │   ┌──────────────────────────────────┐         │
│  📝    │   │  KPI Cards Row                   │         │
│  🔍    │   └──────────────────────────────────┘         │
│  🔗    │   ┌────────────┐  ┌────────────────┐          │
│  📈    │   │ Chart 1    │  │ Chart 2        │          │
│  🤖    │   └────────────┘  └────────────────┘          │
│  ✅    │   ┌──────────────────────────────────┐         │
│  ⚙️    │   │  Data Table / Kanban             │         │
│        │   └──────────────────────────────────┘         │
├────────┴────────────────────────────────────────────────┤
│ ToolSEO v1.0 | Powered by Google Apps Script            │
└─────────────────────────────────────────────────────────┘
```

### Design System
- **Theme**: Dark mode default (agency vibe), light mode toggle
- **Colors**: Deep navy (#0f172a) + Electric blue (#3b82f6) + Success green + Warning amber
- **Typography**: Inter (Google Fonts CDN)
- **Cards**: Glassmorphism subtle (backdrop-blur, semi-transparent)
- **Animations**: Shoelace built-in transitions + Alpine.js x-transition
- **Responsive**: Desktop-first (agency tool, mainly used on desktop)

---

## Phân Phase

### Phase 1 — Foundation & Core Planning (Build this first)
1. ✅ App shell (SPA routing, sidebar, project switcher)
2. ✅ Project Management (multi-site CRUD)
3. ✅ Keyword Research & Mapping
4. ✅ Silo Architecture Builder
5. ✅ Content Outline Generator
6. ✅ Dashboard (basic KPIs)

### Phase 2 — Audit & Checklists
7. Technical Audit Checklist
8. On-Page SEO Checklist
9. GEO Optimization Checklist
10. Template import system

### Phase 3 — Data Integration & Tracking
11. Google Search Console Integration (GSCClient, Performance sync, URL Inspection)
12. Ranking Tracker (GSC auto-sync + manual)
13. Backlink Tracker
14. Advanced Dashboard (GSC metrics + all charts)
15. Client Reporting System (Auto-generate Slides/Docs reports)
16. Quotation & Pricing Engine (Auto-generate pricing from Silo)

---

## Decisions Made (không cần hỏi lại)

| Question | Decision |
|:---|:---|
| Auth model | Single-user (Google account owner). Không cần multi-role. |
| Language UI | **Tiếng Anh** (agency standard), data content tùy user nhập |
| Tailwind mode | CDN Play mode (đơn giản, chấp nhận trade-off perf nhỏ) |
| Content writing | KHÔNG. Chỉ tạo outline/brief rồi export |
| Data import | Có. CSV paste + file upload cho keywords, backlinks, rankings |
| Silo template | Mỗi website khác nhau. Template chỉ là starting point |
| GSC Integration | Có. Auto-sync Performance + URL Inspection + Sitemaps |
| Task Manager | LOẠI BỎ. Quản lý task ngoài app (Notion/Trello/etc.) |
| AI Assistant | Gemini Flash API gọi từ GAS (`GeminiClient.gs`). Free tier ~1500 req/ngày. API key trong Script Properties. Luôn là proposal — user review trước khi apply. |

---

## Verification Plan

### Phase 1 Verification
- Deploy GAS web app (test mode)
- Tạo 1 project mẫu "Construction Nha Trang" từ silo_example.md
- Import ~50 keywords từ CSV
- Build silo structure 3 levels
- Generate 5 content outlines
- Verify dashboard KPIs tính đúng
- Test trên Chrome desktop + mobile responsive

### Phase 3 Verification (GSC)
- Link GCP project + enable GSC API
- Connect 1 GSC property
- Verify searchAnalytics.query returns data
- Verify URL Inspection returns index status
- Verify daily trigger auto-populates rankings sheet
- Verify Dashboard shows GSC KPIs (clicks, impressions, position)

### Performance Target
- Initial load < 3s
- Page switch < 500ms
- Sheet operations < 2s (≤1000 rows)
- GSC sync (100 keywords) < 30s

---

## Future Vision: WP Plugin Integration (Phase 5+)

> **Triết lý**: ToolSEO xử lý toàn bộ phần **Planning** (keyword research, silo, outline, checklist). Một WordPress Plugin riêng biệt xử lý phần **Execution** (AI content generation, publish, sync). Hai hệ thống giao tiếp qua REST API.

### Kiến trúc Tổng thể

```
┌──────────────────────────────┐         ┌────────────────────────────┐
│   ToolSEO (GAS App)          │         │  WP Plugin (Client WP)     │
│   ───── PLANNING ─────       │         │  ───── EXECUTION ─────     │
│                              │  JSON   │                            │
│  Keyword Research ───────────┼────→────┤  Nhận keyword + angle      │
│  Silo Structure ─────────────┼────→────┤  Nhận outline + structure  │
│  Content Outline ────────────┼────→────┤  AI Generate bài viết      │
│  On-page Checklist ──────────┼────→────┤  Auto-check post score     │
│  GEO Checklist ──────────────┼────→────┤  GEO compliance check      │
│                              │         │                            │
│  Ranking Tracker ────────────┼────←────┤  Report post status        │
│  Dashboard KPIs ─────────────┼────←────┤  Sync published URLs       │
└──────────────────────────────┘         └────────────────────────────┘
```

### Data Flow (Quy trình 2 chiều)

**ToolSEO → WP Plugin (Push Brief):**
1. Planner hoàn thành outline trong ToolSEO, click **"Send to WP"**
2. GAS gọi `POST /wp-json/toolseo/v1/import-brief` với payload:
```json
{
  "api_key": "shared_secret",
  "outline_id": "uuid",
  "primary_keyword": "thiết kế nội thất nha trang",
  "secondary_keywords": "nội thất cao cấp, nội thất trọn gói",
  "angle_id": "service_intro",
  "content_type": "service",
  "word_count_target": 1500,
  "h1": "Thiết Kế Nội Thất Nha Trang",
  "outline_structure": [
    {"level": "H2", "heading": "...", "keywords": ["..."]}
  ],
  "internal_links": ["url1", "url2"],
  "meta_title": "...",
  "meta_description": "...",
  "eeat_notes": "...",
  "geo_requirements": "..."
}
```
3. WP Plugin nhận → xếp vào queue → Editor review → Generate → Publish

**WP Plugin → ToolSEO (Sync Status):**
1. Khi post published/updated, WP Plugin tự động callback về ToolSEO
2. `POST [GAS Web App URL]?action=sync_post_status`
3. Payload: `{ outline_id, post_id, url, status, published_date, seo_score }`
4. ToolSEO auto-update `silo_structure.status` → `published`

### Plugin: `toolseo-wp-bridge`

**Thiết kế Generic** (không hardcode brand-specific như XANH AI Content):

```
toolseo-wp-bridge/
├── toolseo-wp-bridge.php         # Bootstrap, activation hooks
├── includes/
│   ├── class-bridge-api.php      # REST endpoints (nhận briefs từ ToolSEO)
│   ├── class-bridge-sync.php     # Callback → sync status về ToolSEO
│   ├── class-bridge-generator.php# Orchestrate AI generation
│   ├── class-bridge-angles.php   # Configurable angles (per-project settings)
│   ├── class-bridge-prompts.php  # Prompt builder (inject outline structure)
│   └── class-bridge-settings.php # API keys, ToolSEO webhook URL config
└── admin/
    ├── class-bridge-admin.php    # Admin UI
    └── views/
        ├── queue.php             # Brief queue: nhận từ ToolSEO, chờ generate
        └── settings.php          # Cấu hình kết nối + AI model
```

### Mapping XANH AI Content → toolseo-wp-bridge

| XANH AI Feature | WP Bridge Equivalent | Thay đổi |
|:---|:---|:---|
| `Xanh_AI_Angles` (8 angles hardcode) | `Bridge_Angles` (stateless, từ brief) | Angles định nghĩa trong ToolSEO, gửi kèm payload |
| `Xanh_AI_Prompts` (7-layer, XANH brand) | `Bridge_Prompts` (inject từ `brand_config`) | Persona + brand voice lấy từ `_config` sheet |
| `Xanh_AI_Generator` | `Bridge_Generator` | Giữ nguyên flow: prompt → API → score → save |
| `Xanh_AI_Score` / On-page checklist | Trả score về ToolSEO | ToolSEO lưu score vào `onpage_checklist` |
| `Xanh_AI_Linker` inject internal links | Silo `internal_links_out/in` | ToolSEO gửi link map qua payload |
| Content Calendar (drag & drop) | Sync với `content_calendar` sheet | 2-way date sync |

---

### Config Architecture: Hybrid "ToolSEO Master + Plugin Technical"

> **Nguyên tắc thiết kế**: ToolSEO là **Single Source of Truth** cho mọi cấu hình nội dung. WP Plugin chỉ giữ phần kỹ thuật (API key, webhook URL). Plugin hoàn toàn **stateless** về content config — mọi thứ kèm theo brief.

#### Phân chia trách nhiệm

| Setting | Thay đổi bao lâu? | Ai manage? | Nằm ở đâu? |
|:---|:---|:---|:---|
| Brand voice / Tone | Hiếm (theo client) | SEO Planner | **ToolSEO `_config`** |
| Persona (tên, kinh nghiệm, số DA) | Hiếm | SEO Planner | **ToolSEO `_config`** |
| Content Angles (type, tone, prompt) | Thỉnh thoảng | SEO Planner | **ToolSEO `_config`** |
| Banned phrases | Hiếm | Agency owner | **ToolSEO `_config`** |
| Outline structure (H2/H3/points) | Mỗi bài | SEO Planner | **ToolSEO → Brief payload** |
| Internal links | Mỗi bài | SEO Planner | **ToolSEO → Brief payload** |
| AI model (Gemini/OpenAI/Claude) | Hiếm | Dev/IT | **WP Plugin settings** |
| AI API Key | Rất hiếm | Dev/IT | **WP Plugin settings** |
| ToolSEO Webhook URL | Một lần | Dev/IT | **WP Plugin settings** |

#### Brief Payload (gửi kèm từng lần generate)

```json
{
  "api_key": "shared_secret",
  "outline_id": "uuid",

  "primary_keyword": "thiết kế nội thất nha trang",
  "secondary_keywords": "nội thất cao cấp, nội thất trọn gói",
  "word_count_target": 1500,
  "h1": "Thiết Kế Nội Thất Nha Trang",
  "outline_structure": [
    {"level": "H2", "heading": "Tại sao chọn...", "keywords": ["kw1"]},
    {"level": "H3", "heading": "Quy trình...", "keywords": ["kw2"]}
  ],
  "internal_links": ["https://example.com/du-an"],
  "meta_title": "...",
  "meta_description": "...",

  "brand_config": {
    "persona_name": "KTS XANH",
    "persona_years": "15",
    "persona_projects": "47+",
    "brand_voice": "Warm Luxury — tinh tế, ấm áp, không lạnh lẽo",
    "banned_phrases": ["Giá rẻ", "Khuyến mãi", "Số 1"],
    "angle": {
      "id": "service_intro",
      "label": "Giới Thiệu Dịch Vụ",
      "tone": "Expert + Warm Luxury",
      "prompt_template": "Viết bài giới thiệu dịch vụ...",
      "cta_primary": "Đặt Lịch Tư Vấn",
      "min_words": 1000,
      "image_style": "Professional interior, warm ambient",
      "opening_style": "aspiration",
      "closing_style": "invitation"
    }
  }
}
```

> Plugin nhận → build prompt từ `brand_config` → generate → **không lưu gì về config** → callback status về ToolSEO.

#### Multi-client Management (Nhiều Web)

```
ToolSEO
│
├── 🌐 Global Angle Library (Master Sheet — agency-level)
│   ├── "service_intro" — template chuẩn cho mọi client
│   ├── "knowledge"     — có thể inherit + override per project
│   ├── "case_study"
│   └── ...
│
├── 📁 Project A: XANH Design (xanhdesignbuild.com)
│   ├── _config:
│   │   ├── persona_name: "KTS XANH", years: "15", projects: "47+"
│   │   ├── brand_voice: "Warm Luxury"
│   │   ├── angles_override: { "service_intro": { cta: "Đặt Lịch Riêng" } }
│   │   ├── banned_phrases: ["Giá rẻ", "Khuyến mãi"]
│   │   └── wp_url: "https://xanhdesignbuild.com"
│   └── (WP Plugin chỉ có: API Key + Webhook URL)
│
├── 📁 Project B: Client XYZ (xyz.com)
│   ├── _config:
│   │   ├── persona_name: "Chuyên gia ABC", years: "10", projects: "30+"
│   │   ├── brand_voice: "Professional + Trustworthy"
│   │   ├── angles_override: { dùng 3/8 angles }
│   │   └── wp_url: "https://xyz.com"
│   └── (WP Plugin hoàn toàn khác, cùng plugin code)
│
└── 📁 Project C: Client DEF
    └── ...
```

**Kết quả**: Một Planner ngồi trong ToolSEO → cấu hình brand voice cho 10 client → push brief → mỗi WP site tự generate đúng giọng văn client đó. **Không phải đăng nhập vào từng WP site để config.**

#### `_config` Sheet Schema (Bổ sung cho Phase 5)

| key | value_example | description |
|:---|:---|:---|
| `persona_name` | KTS XANH | Tên chuyên gia / brand persona |
| `persona_years` | 15 | Số năm kinh nghiệm |
| `persona_projects` | 47+ | Số dự án hoàn thành |
| `brand_voice` | Warm Luxury | Tông giọng tổng thể |
| `brand_accuracy` | 98% | Số liệu thương hiệu (tùy brand) |
| `banned_phrases_json` | `["Giá rẻ","Khuyến mãi"]` | Từ cấm (JSON array) |
| `angles_json` | `[{id, tone, prompt_template,...}]` | Angles config của project |
| `wp_url` | https://client.com | WP site URL |
| `wp_api_key` | encrypted_string | API key kết nối WP Bridge |
| `wp_sync_enabled` | true | Bật/tắt 2-way sync |
| `ai_model_preference` | gemini-2.5-flash | Model ưu tiên (plugin có thể override) |

#### Tóm tắt quyết định kiến trúc

| Câu hỏi | Quyết định |
|:---|:--|
| Topic / Outline structure ở đâu? | **ToolSEO** — Planner tạo, gửi kèm brief |
| Angle / Content type ở đâu? | **ToolSEO `_config`** — Planner cấu hình per project |
| Brand voice / Persona ở đâu? | **ToolSEO `_config`** — 1 nơi cho nhiều web |
| Banned phrases ở đâu? | **ToolSEO `_config`** — Agency standard + per-project override |
| API Key / AI Model ở đâu? | **WP Plugin** — IT setup 1 lần |
| Plugin có cần lưu content config? | **KHÔNG** — stateless, nhận từ payload |

### Thêm vào ToolSEO (Phase 5 requirements)

- **`_config` tab** mở rộng: Thêm brand voice, persona, angles, banned phrases fields
- **Angle Library UI** (trong Settings): CRUD angles, test prompt template preview
- **Outline → "Send to WP" button**: Bundle brief + `brand_config` → POST to WP Bridge
- **WP Connection Manager** (trong Settings): Lưu `wp_url`, `wp_api_key` per project
- **Post Status Webhook Receiver**: GAS `doPost()` nhận callback từ WP Bridge
- **Silo: Published badge**: ✅ khi URL đã synced về từ WP Plugin
- **Content Calendar**: View lịch publish từ WP Plugin (2-way sync)
