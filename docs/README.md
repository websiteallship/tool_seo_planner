# ToolSEO Planner — Documentation Hub

> **SEO Planning Management App** for agencies.  
> Stack: Google Apps Script · Google Sheets · Alpine.js · Tailwind CSS · Shoelace · Gemini AI

---

## Quick Start

1. **Setup**: Xem [OPS_DEPLOYMENT.md](./OPS_DEPLOYMENT.md)
2. **Onboard AI**: Xem [REF_AI_HANDOVER.md](./REF_AI_HANDOVER.md)
3. **Hiểu dự án**: Đọc [CORE_PROJECT.md](./CORE_PROJECT.md)
4. **Roadmap**: Xem [TRACK_ROADMAP.md](./TRACK_ROADMAP.md)

---

## Cấu Trúc Tài Liệu

### CORE — Kiến Trúc Lõi
| File | Mô tả |
|---|---|
| [CORE_PROJECT.md](./CORE_PROJECT.md) | Tổng quan dự án, tech stack, folder structure, conventions |
| [CORE_FEATURES.md](./CORE_FEATURES.md) | Danh sách features theo phase và priority |
| [CORE_ARCHITECTURE.md](./CORE_ARCHITECTURE.md) | Kiến trúc hệ thống, data flow, constraints |
| [CORE_DATABASE.md](./CORE_DATABASE.md) | Schema tất cả Google Sheets |
| [CORE_AI_CONTEXT.md](./CORE_AI_CONTEXT.md) | Gemini AI integration, prompt templates |

### WORKFLOW
| File | Mô tả |
|---|---|
| [WORKFLOW_OVERVIEW.md](./WORKFLOW_OVERVIEW.md) | Luồng nghiệp vụ tổng thể (10 bước) |

### ARCH — Thiết Kế Kiến Trúc
| File | Mô tả |
|---|---|
| [ARCH_PATTERNS.md](./ARCH_PATTERNS.md) | Design patterns: Service, SheetDB, API dispatcher, Alpine.js |
| [ARCH_INTEGRATIONS.md](./ARCH_INTEGRATIONS.md) | GSC API, Drive, Slides, Docs code examples |
| [ARCH_PERFORMANCE.md](./ARCH_PERFORMANCE.md) | GAS quotas, batch patterns, caching strategy |

### GOV — Quy Chuẩn
| File | Mô tả |
|---|---|
| [GOV_CODING_STANDARDS.md](./GOV_CODING_STANDARDS.md) | Naming conventions, error handling, absolute rules |
| [GOV_UX_GUIDELINES.md](./GOV_UX_GUIDELINES.md) | Shoelace components, layout, UX micro-copy |

### MODULE — Chi Tiết Từng Module
| File | Module | Phase |
|---|---|---|
| [MODULE_PROJECT.md](./MODULE_PROJECT.md) | M1: Project Management | Phase 1 |
| [MODULE_KEYWORD.md](./MODULE_KEYWORD.md) | M2: Keyword Research & Mapping | Phase 1 |
| [MODULE_SILO.md](./MODULE_SILO.md) | M3: Silo Architecture & URL Structure | Phase 1 |
| [MODULE_OUTLINE.md](./MODULE_OUTLINE.md) | M4: Content Outline Generator | Phase 1 |
| [MODULE_AUDIT.md](./MODULE_AUDIT.md) | M5+M6+M7: Technical + On-Page + GEO Audit | Phase 2 |
| [MODULE_GSC.md](./MODULE_GSC.md) | M10: Google Search Console Integration | Phase 3 |
| [MODULE_DASHBOARD.md](./MODULE_DASHBOARD.md) | M11: Dashboard & KPI Overview | Phase 1 |
| [MODULE_REPORTING.md](./MODULE_REPORTING.md) | M12+M13: Client Reporting + Quotation | Phase 2 |

### OPS — Vận Hành
| File | Mô tả |
|---|---|
| [OPS_DEPLOYMENT.md](./OPS_DEPLOYMENT.md) | Setup, deploy GAS, configure triggers |
| [OPS_TESTING.md](./OPS_TESTING.md) | QA checklist, test cases, debug helpers |

### REF — Tài Liệu Tham Khảo
| File | Mô tả |
|---|---|
| [REF_AI_HANDOVER.md](./REF_AI_HANDOVER.md) | Context document cho AI assistant onboarding |

### TRACK — Theo Dõi Tiến Độ
| File | Mô tả |
|---|---|
| [TRACK_ROADMAP.md](./TRACK_ROADMAP.md) | Roadmap 5 phases + milestones checklist |
| [TRACK_DECISIONS.md](./TRACK_DECISIONS.md) | Architecture Decision Records (ADR 001-007) |
| [TRACK_CHANGELOG.md](./TRACK_CHANGELOG.md) | Lịch sử thay đổi theo version |

---

## 14 Modules

| # | Module | Sheet | Status |
|---|---|---|---|
| M1 | Project Management | `_projects` | Phase 1 |
| M2 | Keyword Research & Mapping | `keywords` | Phase 1 |
| M3 | Silo Architecture | `silo_structure` | Phase 1 |
| M4 | Content Outline Generator | `content_outlines` | Phase 1 |
| M5 | Technical Audit Checklist | `technical_audit` | Phase 2 |
| M6 | On-Page SEO Checklist | `onpage_checklist` | Phase 2 |
| M7 | GEO Optimization Checklist | `geo_checklist` | Phase 2 |
| M8 | Backlink Tracker | `backlinks` | Phase 3 |
| M9 | Ranking Tracker | `rankings` | Phase 3 |
| M10 | GSC Integration | `gsc_performance`, `gsc_index_status` | Phase 3 |
| M11 | Dashboard / Project Overview | (aggregated) | Phase 1 |
| M12 | Client Reporting System | (Drive files) | Phase 2 |
| M13 | Quotation & Pricing Engine | `rate_card`, `quotation` | Phase 2 |
| M14 | Gemini AI Copilot | (no sheet) | Phase 2 |
