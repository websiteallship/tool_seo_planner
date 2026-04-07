// services/ProjectService.gs — M1: Project Management
// Quản lý dự án SEO: CRUD, auto-tạo sheet từ template, archive, duplicate

const MASTER_SHEET_NAME = '_projects';

// Schema headers cho sheet _projects trong Master Sheet
const PROJECT_HEADERS = [
  'project_id', 'name', 'client_name', 'domain', 'status',
  'gsc_site_url', 'spreadsheet_id', 'notes', 'created_at', 'updated_at'
];

// Per-Project Sheet — 13 tabs theo CORE_DATABASE.md
const PROJECT_SHEET_TABS = {
  // Tab 1: Project config
  _config:            ['key', 'value', 'description'],

  // Tab 2: Keywords (M2)
  keywords:           ['keyword_id', 'keyword', 'search_volume', 'difficulty', 'cpc', 'intent',
                       'cluster_group', 'silo_id', 'target_url', 'current_position', 'priority',
                       'status', 'lsi_keywords', 'source', 'notes'],

  // Tab 3: Silo Structure (M3)
  silo_structure:     ['silo_id', 'silo_name', 'silo_type', 'parent_id', 'level', 'target_url',
                       'primary_keyword', 'secondary_keywords', 'search_intent', 'h1_tag',
                       'meta_title', 'meta_description', 'internal_links_out', 'internal_links_in',
                       'word_count_target', 'content_type', 'schema_types', 'status', 'order', 'notes'],

  // Tab 4: Content Outlines (M4)
  content_outlines:   ['outline_id', 'silo_id', 'target_url', 'working_title', 'primary_keyword',
                       'secondary_keywords', 'search_intent', 'target_word_count', 'meta_title',
                       'meta_description', 'h1_tag', 'outline_structure', 'internal_links',
                       'external_links', 'schema_required', 'eeat_notes', 'geo_requirements',
                       'tone', 'competitor_urls', 'status', 'assigned_to', 'notes'],

  // Tab 5: Technical Audit (M5)
  technical_audit:    ['audit_id', 'category', 'item', 'severity', 'status', 'url', 'details',
                       'recommendation', 'assigned_to', 'due_date', 'fixed_date', 'evidence', 'notes'],

  // Tab 6: On-page Checklist (M6)
  onpage_checklist:   ['check_id', 'url', 'silo_id', 'category', 'item', 'status',
                       'current_value', 'recommended_value', 'notes'],

  // Tab 7: GEO Checklist (M7)
  geo_checklist:      ['geo_id', 'url', 'silo_id', 'category', 'item', 'status', 'details', 'notes'],

  // Tab 8: Backlinks (M8)
  backlinks:          ['backlink_id', 'referring_domain', 'referring_url', 'target_url',
                       'anchor_text', 'link_type', 'domain_authority', 'status',
                       'outreach_date', 'live_date', 'contact_info', 'cost', 'campaign', 'notes'],

  // Tab 9: Rankings Tracker (M9)
  rankings:           ['tracking_id', 'keyword', 'target_url', 'position', 'previous_position',
                       'change', 'best_position', 'clicks', 'impressions', 'ctr', 'device',
                       'country', 'tracked_date', 'data_source', 'featured_snippet',
                       'ai_overview', 'serp_features', 'notes'],

  // Tab 10: GSC Performance (M10)
  gsc_performance:    ['record_id', 'query', 'page', 'clicks', 'impressions', 'ctr',
                       'position', 'country', 'device', 'date', 'synced_at'],

  // Tab 11: GSC Index Status (M10)
  gsc_index_status:   ['inspection_id', 'url', 'verdict', 'coverage_state', 'robotstxt_state',
                       'indexing_state', 'page_fetch_state', 'crawled_as', 'canonical_url',
                       'user_canonical', 'mobile_usability', 'mobile_issues',
                       'last_crawl_time', 'inspected_at'],

  // Tab 12: Rate Card (M13)
  rate_card:          ['item_id', 'category', 'item_name', 'unit', 'unit_price', 'tier', 'currency', 'notes'],

  // Tab 13: Quotation (M13)
  quotation:          ['quote_id', 'created_date', 'version', 'status', 'total_amount',
                       'currency', 'breakdown', 'notes'],

  // Tab 14: Reports log
  _reports:           ['report_id', 'report_type', 'generated_date', 'period',
                       'drive_url', 'drive_file_id', 'status', 'notes'],
};

const ProjectService = {
  /**
   * Lấy ID của Master Sheet từ Script Properties (auto-set bởi SetupService khi lưu Folder ID)
   */
  _getMasterSheetId() {
    const id = PropertiesService.getScriptProperties().getProperty('MASTER_SHEET_ID');
    if (!id) throw new Error('MASTER_SHEET_ID not configured — please save Drive Folder ID in Settings');
    return id;
  },

  /**
   * Lấy Drive Folder ID từ Script Properties
   * @returns {string|null}
   */
  _getDriveFolderId() {
    return PropertiesService.getScriptProperties().getProperty('DRIVE_FOLDER_ID') || null;
  },

  /**
   * Lấy spreadsheet_id của project từ master sheet
   * @param {string} projectId
   * @returns {string}
   */
  getSpreadsheetId(projectId) {
    const project = SheetDB.findOne(this._getMasterSheetId(), MASTER_SHEET_NAME, 'project_id', projectId);
    if (!project) throw new Error(`Project not found: ${projectId}`);
    if (!project.spreadsheet_id) throw new Error(`Project '${projectId}' has no spreadsheet_id — run Seed Data to initialize`);
    return project.spreadsheet_id;
  },

  /**
   * Lấy tất cả projects (không bao gồm archived)
   */
  getAll() {
    return SheetDB.getRows(this._getMasterSheetId(), MASTER_SHEET_NAME);
  },

  /**
   * Tạo project mới — auto tạo Google Sheet từ template
   * @param {Object} data — { name, client_name, domain, gsc_site_url?, notes? }
   */
  create(data) {
    validateRequired(data, ['name', 'domain']);
    const projectId = uuid();
    const now = nowISO();

    // Tạo Google Sheet mới cho project, đặt vào Drive folder nếu đã cấu hình
    const ss = SpreadsheetApp.create(`ToolSEO — ${data.name}`);
    const spreadsheetId = ss.getId();
    const folderId = this._getDriveFolderId();
    if (folderId) {
      try {
        DriveApp.getFileById(spreadsheetId).moveTo(DriveApp.getFolderById(folderId));
      } catch (e) {
        Logger.log(`[ProjectService] WARN: Could not move sheet to folder: ${e.message}`);
      }
    }
    const defaultSheet = ss.getSheets()[0]; // Sheet1 mặc định

    // Tạo tất cả tabs theo schema PROJECT_SHEET_TABS
    const tabNames = Object.keys(PROJECT_SHEET_TABS);
    tabNames.forEach((tabName, idx) => {
      let sheet;
      if (idx === 0) {
        // Đổi tên sheet mặc định thành tab đầu tiên (_config)
        sheet = defaultSheet;
        sheet.setName(tabName);
      } else {
        sheet = ss.insertSheet(tabName);
      }
      const headers = PROJECT_SHEET_TABS[tabName];
      sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
      sheet.setFrozenRows(1);
    });

    // Seed _config defaults
    ProjectService._seedConfig(ss, data);

    // Lưu vào Master Sheet
    const row = {
      project_id: projectId,
      name: data.name,
      client_name: data.client_name || '',
      domain: data.domain,
      status: 'active',
      gsc_site_url: data.gsc_site_url || '',
      spreadsheet_id: spreadsheetId,
      notes: data.notes || '',
      created_at: now,
      updated_at: now
    };

    SheetDB.addRow(this._getMasterSheetId(), MASTER_SHEET_NAME, row);
    Logger.log(`[ProjectService] INFO: Created project '${data.name}' (${projectId})`);
    return row;
  },

  /**
   * Cập nhật thông tin project
   */
  update(projectId, data) {
    data.updated_at = nowISO();
    const ok = SheetDB.updateRow(this._getMasterSheetId(), MASTER_SHEET_NAME, 'project_id', projectId, data);
    if (!ok) throw new Error(`Project not found: ${projectId}`);
    return { project_id: projectId, ...data };
  },

  /**
   * Archive project — set status='archived', sheet vẫn còn
   */
  archive(projectId) {
    return this.update(projectId, { status: 'archived' });
  },

  /**
   * Xoá project và sheet tương ứng
   */
  delete(projectId) {
    const project = SheetDB.findOne(this._getMasterSheetId(), MASTER_SHEET_NAME, 'project_id', projectId);
    if (!project) throw new Error(`Project not found: ${projectId}`);

    // Xoá Google Sheet
    if (project.spreadsheet_id) {
      try {
        DriveApp.getFileById(project.spreadsheet_id).setTrashed(true);
      } catch (e) {
        Logger.log(`[ProjectService] WARN: Could not trash sheet ${project.spreadsheet_id}: ${e.message}`);
      }
    }

    return SheetDB.deleteRow(this._getMasterSheetId(), MASTER_SHEET_NAME, 'project_id', projectId);
  },

  /**
   * Duplicate project — clone settings, tạo sheet mới rỗng
   */
  duplicate(projectId) {
    const source = SheetDB.findOne(this._getMasterSheetId(), MASTER_SHEET_NAME, 'project_id', projectId);
    if (!source) throw new Error(`Project not found: ${projectId}`);
    return this.create({
      name: `${source.name} (Copy)`,
      client_name: source.client_name,
      domain: source.domain,
      gsc_site_url: source.gsc_site_url,
      notes: source.notes
    });
  },

  /**
   * Seed _config tab với default values khi tạo project mới
   * @param {GoogleAppsScript.Spreadsheet.Spreadsheet} ss
   * @param {Object} data — project data
   */
  _seedConfig(ss, data) {
    const configSheet = ss.getSheetByName('_config');
    if (!configSheet) return;
    const defaults = [
      ['brand_name',                data.name || '',      'Tên website / thương hiệu'],
      ['target_audience',           '',                   'Mô tả đối tượng mục tiêu'],
      ['tone_of_voice',             'professional',       'professional / friendly / authoritative'],
      ['blacklist_words',           '',                   'Từ cấm dùng (comma-sep)'],
      ['gemini_model',              'gemini-1.5-flash',   'Gemini model ID'],
      ['report_template_slides_id', '',                   'Google Slides template ID cho báo cáo'],
      ['report_template_docs_id',   '',                   'Google Docs template ID'],
      ['rate_card_currency',        'VND',                'Đơn vị tiền tệ mặc định'],
    ];
    defaults.forEach(([key, value, description]) => {
      configSheet.appendRow([key, value, description]);
    });
  }
};
