# MODULE_DASHBOARD — Dashboard & KPI Overview (M11)

## Logic & Workflow Nghiệp Vụ

### Cách Dashboard Hoạt Động
```
User load Dashboard page (hoặc switch project)
  → DashboardService.getMetrics(projectId):
      Aggregate data từ nhiều sheets cùng lúc:
        gsc_performance → clicks, impressions, ctr, avg position (28 ngày)
        keywords → total, mapped count, intent breakdown
        silo_structure → pages planned vs published
        content_outlines → outlines created, sent to writer
        technical_audit → pass%, open critical items
        onpage_checklist → avg score across pages
        geo_checklist → avg GEO score
        backlinks → live count, avg DA
        rankings → Top 10 count, distribution
  → Trả về 1 object chứa tất cả KPIs
  → UI render KPI cards + Charts (Chart.js)
  → Cache result 5 phút (CacheService)
```

### Refresh Logic
- **Auto-refresh**: không auto-refresh liên tục (tránh quota)
- **Reload trang**: trigger load mới từ server
- **"Sync GSC"**: manual trigger + refresh dashboard sau khi sync xong
- **Cache**: 5 phút TTL — đủ fresh cho nhu cầu daily management

---

## KPI Cards (11 metrics)

| KPI | Nguồn Data | Ý nghĩa |
|---|---|---|
| 📈 Total Clicks (28d) | gsc_performance | Traffic từ organic search |
| 👁️ Total Impressions (28d) | gsc_performance | Visibility trên SERP |
| 📍 Avg Position | gsc_performance | Thứ hạng trung bình |
| 🔑 Keywords: Total / Mapped | keywords sheet | Tiến độ keyword mapping |
| 🏗️ Silo: Planned / Published | silo_structure | Tiến độ content production |
| 📝 Outlines: Created / Sent | content_outlines | Brief delivery rate |
| 🔍 Technical Audit Score | technical_audit | % items passed |
| ✅ On-Page Avg Score | onpage_checklist | Quality across pages |
| 🤖 GEO Readiness Score | geo_checklist | AI search readiness |
| 🔗 Backlinks: Live / Avg DA | backlinks | Link building progress |
| 📊 Keywords Top 10 | rankings | Ranking performance |

---

## Charts (Chart.js 4.x)

| Chart | Type | Data |
|---|---|---|
| GSC Performance Trend | Line (2 series) | clicks + impressions, 28 ngày |
| Keyword Intent Distribution | Doughnut | Count per intent |
| Silo Progress | Horizontal Bar | pages by status |
| Ranking Distribution | Pie | Top 3 / 4-10 / 11-20 / 21-50 / 50+ |
| Audit Issues by Severity | Stacked Bar | critical/high/medium by category |
| GEO Readiness | Radar | 4 dimensions |
| Backlink Growth | Line | cumulative live links over time |
| Device Split | Bar | Desktop vs Mobile clicks |

---

## Quick Actions

- **🔄 Sync GSC Data**: trigger manual GSC sync
- **📋 Run Full Audit**: navigate to Audit + load template
- **📝 Generate Outlines**: navigate to Outline + batch generate
- **📊 Export Summary**: generate project summary Google Doc

---

## Database

Dashboard không có sheet riêng — aggregate on-the-fly từ các sheets khác.

DashboardService đọc (read-only):
- `gsc_performance` → clicks, impressions, ctr, position
- `keywords` → count, status, intent
- `silo_structure` → count by status
- `content_outlines` → count by status
- `technical_audit` → pass rate, open criticals
- `onpage_checklist` → avg score
- `geo_checklist` → avg score
- `backlinks` → live count, avg DA
- `rankings` → distribution counts

---

## Service API

```javascript
DashboardService.getMetrics(projectId)
// → {
//     traffic: {clicks, impressions, ctr, position, trend[]},
//     keywords: {total, mapped, byIntent{}},
//     silo: {planned, published, byStatus{}},
//     outlines: {created, sent, completed},
//     audit: {score, openCriticals},
//     onpage: {avgScore},
//     geo: {avgScore},
//     backlinks: {live, avgDA},
//     rankings: {top3, top10, distribution{}}
//   }
```
