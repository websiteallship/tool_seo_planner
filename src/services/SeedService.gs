// services/SeedService.gs — Developer seed data cho demo/testing
// Gọi seed.run(projectId) để điền đủ data vào tất cả sheets của 1 project
// CẢNH BÁO: Chỉ dùng cho dev/demo — xoá data khi seed lại

const SeedService = {

  /**
   * Entry point — seed tất cả modules cho 1 project
   * @param {string} projectId
   * @returns {Object} summary
   */
  run(projectId) {
    // Lấy spreadsheet_id của project — auto-tạo nếu chưa có
    let ssId;
    try {
      ssId = ProjectService.getSpreadsheetId(projectId);
    } catch (e) {
      // Project chưa có spreadsheet riêng — tự tạo trong Drive folder
      ssId = SeedService._createProjectSheet(projectId);
    }

    const now = nowISO();
    const today = Utilities.formatDate(new Date(), 'Asia/Ho_Chi_Minh', 'yyyy-MM-dd');

    // STEP 1: Đảm bảo tất cả tabs tồn tại với đúng headers
    const tabsCreated = SeedService._ensureTabs(ssId);
    if (tabsCreated.length > 0) {
      Logger.log(`[SeedService] Created missing tabs: ${tabsCreated.join(', ')}`);
    }

    // STEP 2: Xóa data cũ (giữ headers) trước khi seed
    SeedService._clearAllData(ssId);

    // Tạo IDs seed cố định để dùng lại cross-sheet (FK)
    const IDS = SeedService._buildIds();

    const results = {};
    const errors = {};

    const seedTasks = [
      ['keywords',         () => SeedService._seedKeywords(ssId, IDS, now)],
      ['silo_structure',   () => SeedService._seedSilo(ssId, IDS, now)],
      ['content_outlines', () => SeedService._seedOutlines(ssId, IDS, now)],
      ['technical_audit',  () => SeedService._seedTechAudit(ssId, IDS, now)],
      ['onpage_checklist', () => SeedService._seedOnpage(ssId, IDS, now)],
      ['geo_checklist',    () => SeedService._seedGeo(ssId, IDS, now)],
      ['backlinks',        () => SeedService._seedBacklinks(ssId, IDS, today, now)],
      ['rankings',         () => SeedService._seedRankings(ssId, IDS, today)],
      ['gsc_performance',  () => SeedService._seedGSCPerformance(ssId, IDS, today, now)],
      ['gsc_index_status', () => SeedService._seedGSCIndexStatus(ssId, IDS, now)],
      ['rate_card',        () => SeedService._seedRateCard(ssId, IDS)],
      ['quotation',        () => SeedService._seedQuotation(ssId, IDS, today)],
    ];

    seedTasks.forEach(([name, fn]) => {
      try {
        results[name] = fn();
      } catch (e) {
        errors[name] = e.message;
        Logger.log(`[SeedService] ERROR seeding '${name}': ${e.message}`);
      }
    });

    const hasErrors = Object.keys(errors).length > 0;
    Logger.log(`[SeedService] INFO: Seeded project ${projectId}: ${JSON.stringify(results)}${hasErrors ? ' ERRORS: ' + JSON.stringify(errors) : ''}`);
    return { ok: !hasErrors, projectId, tabsCreated, results, errors: hasErrors ? errors : undefined };
  },

  /**
   * Đảm bảo tất cả tabs theo PROJECT_SHEET_TABS tồn tại trong spreadsheet
   * Tạo tab mới + headers nếu chưa có
   * @param {string} ssId
   * @returns {string[]} danh sách tabs vừa tạo mới
   */
  _ensureTabs(ssId) {
    const ss = SpreadsheetApp.openById(ssId);
    const existingSheets = ss.getSheets().map(s => s.getName());
    const created = [];

    Object.entries(PROJECT_SHEET_TABS).forEach(([tabName, headers]) => {
      if (!existingSheets.includes(tabName)) {
        const sheet = ss.insertSheet(tabName);
        sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
        sheet.setFrozenRows(1);
        created.push(tabName);
      } else {
        // Tab tồn tại — kiểm tra headers có đúng không
        const sheet = ss.getSheetByName(tabName);
        const lastCol = sheet.getLastColumn();
        if (lastCol === 0) {
          // Sheet trống hoàn toàn — thêm headers
          sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
          sheet.setFrozenRows(1);
          created.push(tabName + '(headers)');
        }
      }
    });

    // Xóa sheet mặc định "Sheet1" nếu còn tồn tại và có nhiều hơn 1 sheet
    if (ss.getSheets().length > 1) {
      const defaultSheet = ss.getSheetByName('Sheet1');
      if (defaultSheet) {
        ss.deleteSheet(defaultSheet);
      }
    }

    return created;
  },

  /**
   * Xóa tất cả data rows (giữ header row 1) trên các tabs seed
   * @param {string} ssId
   */
  _clearAllData(ssId) {
    const ss = SpreadsheetApp.openById(ssId);
    const seedTabs = [
      'keywords', 'silo_structure', 'content_outlines', 'technical_audit',
      'onpage_checklist', 'geo_checklist', 'backlinks', 'rankings',
      'gsc_performance', 'gsc_index_status', 'rate_card', 'quotation'
    ];
    seedTabs.forEach(tabName => {
      const sheet = ss.getSheetByName(tabName);
      if (sheet && sheet.getLastRow() > 1) {
        sheet.getRange(2, 1, sheet.getLastRow() - 1, sheet.getLastColumn()).clearContent();
      }
    });
  },

  /**
   * Tạo spreadsheet mới trong Drive folder cho project chưa có sheet
   * Cập nhật master record với spreadsheet_id mới
   * @param {string} projectId
   * @returns {string} spreadsheetId mới tạo
   */
  _createProjectSheet(projectId) {
    const masterSheetId = PropertiesService.getScriptProperties().getProperty('MASTER_SHEET_ID');
    const folderId = PropertiesService.getScriptProperties().getProperty('DRIVE_FOLDER_ID');
    if (!masterSheetId) throw new Error('Drive Folder ID not configured in Settings');

    // Lấy thông tin project từ master
    const project = SheetDB.findOne(masterSheetId, '_projects', 'project_id', projectId);
    if (!project) throw new Error(`Project not found: ${projectId}`);

    const sheetName = `ToolSEO — ${project.name || projectId}`;

    // Tạo spreadsheet mới
    const ss = SpreadsheetApp.create(sheetName);
    const spreadsheetId = ss.getId();

    // Di chuyển vào folder nếu có cấu hình
    if (folderId) {
      try {
        DriveApp.getFileById(spreadsheetId).moveTo(DriveApp.getFolderById(folderId));
      } catch (e) {
        Logger.log(`[SeedService] WARN: Could not move sheet to folder: ${e.message}`);
      }
    }

    // Cập nhật spreadsheet_id vào master record
    SheetDB.updateRow(masterSheetId, '_projects', 'project_id', projectId, {
      spreadsheet_id: spreadsheetId,
      updated_at: nowISO()
    });

    Logger.log(`[SeedService] INFO: Created project sheet '${sheetName}' (${spreadsheetId})`);
    return spreadsheetId;
  },

  /** Tạo bộ IDs seed cố định để FK cross-sheet nhất quán */
  _buildIds() {
    return {
      // Silo IDs
      silo_home:      'silo-home-001',
      silo_service:   'silo-svc-001',
      silo_blog:      'silo-blog-001',
      silo_audit:     'silo-audit-001',
      silo_pricing:   'silo-price-001',

      // Keyword IDs
      kw_main:        'kw-main-001',
      kw_how_to:      'kw-howto-001',
      kw_review:      'kw-review-001',
      kw_price:       'kw-price-001',
      kw_compare:     'kw-cmp-001',
      kw_local:       'kw-local-001',
      kw_question:    'kw-q-001',
      kw_longtail:    'kw-lt-001',

      // Outline IDs
      outline_home:   'outline-home-001',
      outline_svc:    'outline-svc-001',
      outline_guide:  'outline-guide-001',
    };
  },

  // ── M2: Keywords ────────────────────────────────────────────
  _seedKeywords(ssId, IDS, now) {
    const rows = [
      // keyword_id, keyword, search_volume, difficulty, cpc, intent, cluster_group, silo_id, target_url, current_position, priority, status, lsi_keywords, source, notes
      { keyword_id: IDS.kw_main,     keyword: 'dịch vụ seo website',         search_volume: 12400, difficulty: 68, cpc: 1.2,  intent: 'commercial',      cluster_group: 'Core Service',   silo_id: IDS.silo_service, target_url: '/dich-vu-seo',        current_position: 7,  priority: 'critical', status: 'targeting',    lsi_keywords: 'tối ưu seo, seo onpage, seo offpage', source: 'Ahrefs',  notes: 'Keyword chính, ưu tiên cao nhất' },
      { keyword_id: IDS.kw_how_to,   keyword: 'cách làm seo cho người mới',  search_volume: 8200,  difficulty: 45, cpc: 0.6,  intent: 'informational',   cluster_group: 'Education',      silo_id: IDS.silo_blog,    target_url: '/blog/seo-co-ban',    current_position: 14, priority: 'high',     status: 'mapped',       lsi_keywords: 'học seo, seo là gì, hướng dẫn seo', source: 'SEMrush', notes: '' },
      { keyword_id: IDS.kw_review,   keyword: 'review công cụ seo tốt nhất', search_volume: 5600,  difficulty: 52, cpc: 0.8,  intent: 'commercial',      cluster_group: 'Tools Review',   silo_id: IDS.silo_blog,    target_url: '/blog/cong-cu-seo',   current_position: 23, priority: 'high',     status: 'researching',  lsi_keywords: 'ahrefs, semrush, google search console', source: 'manual',  notes: 'Cần bài so sánh chi tiết' },
      { keyword_id: IDS.kw_price,    keyword: 'bảng giá dịch vụ seo',        search_volume: 3900,  difficulty: 41, cpc: 1.5,  intent: 'transactional',   cluster_group: 'Pricing',        silo_id: IDS.silo_pricing,  target_url: '/bang-gia-seo',       current_position: 5,  priority: 'critical', status: 'ranking',      lsi_keywords: 'chi phí seo, giá seo tháng', source: 'Ahrefs',  notes: 'Đang top 5 GSC' },
      { keyword_id: IDS.kw_compare,  keyword: 'so sánh agency seo hcm',      search_volume: 2800,  difficulty: 38, cpc: 2.1,  intent: 'commercial',      cluster_group: 'Competitor',     silo_id: IDS.silo_service, target_url: '/so-sanh-agency-seo', current_position: 31, priority: 'medium',   status: 'researching',  lsi_keywords: 'agency seo uy tín, công ty seo', source: 'SEMrush', notes: '' },
      { keyword_id: IDS.kw_local,    keyword: 'dịch vụ seo tại hà nội',      search_volume: 2100,  difficulty: 35, cpc: 1.1,  intent: 'transactional',   cluster_group: 'Local SEO',      silo_id: IDS.silo_service, target_url: '/seo-ha-noi',         current_position: 18, priority: 'high',     status: 'mapped',       lsi_keywords: 'seo hà nội, dịch vụ seo hn', source: 'manual',  notes: 'Local landing page cần optimize' },
      { keyword_id: IDS.kw_question, keyword: 'seo có hiệu quả không',       search_volume: 1800,  difficulty: 30, cpc: 0.4,  intent: 'informational',   cluster_group: 'FAQ',            silo_id: IDS.silo_blog,    target_url: '/blog/seo-hieu-qua',  current_position: 42, priority: 'medium',   status: 'researching',  lsi_keywords: 'lợi ích seo, tại sao cần seo', source: 'SEMrush', notes: 'Cần featured snippet' },
      { keyword_id: IDS.kw_longtail, keyword: 'cách kiểm tra thứ hạng từ khóa miễn phí', search_volume: 960, difficulty: 22, cpc: 0.3, intent: 'informational', cluster_group: 'Tools', silo_id: IDS.silo_blog, target_url: '/blog/kiem-tra-thu-hang', current_position: 9, priority: 'low', status: 'ranking', lsi_keywords: 'check rank keyword, google rank checker', source: 'Ahrefs', notes: 'Longtail đang rank tốt' },
    ];
    SheetDB.batchAddRows(ssId, 'keywords', rows);
    return rows.length;
  },

  // ── M3: Silo Structure ──────────────────────────────────────
  _seedSilo(ssId, IDS, now) {
    const rows = [
      { silo_id: IDS.silo_home,    silo_name: 'Trang Chủ',               silo_type: 'homepage',      parent_id: '',             level: 0, target_url: '/',                    primary_keyword: 'dịch vụ seo website', secondary_keywords: 'agency seo, công ty seo', search_intent: 'commercial',    h1_tag: 'Dịch Vụ SEO Website Uy Tín #1 Việt Nam',       meta_title: 'Dịch Vụ SEO Website #1 - Tăng Traffic Hữu Cơ',                  meta_description: 'Cung cấp dịch vụ SEO toàn diện giúp website lên top Google. Tư vấn miễn phí.', internal_links_out: '/dich-vu-seo|/bang-gia-seo|/blog', internal_links_in: '', word_count_target: 1500, content_type: 'landing', schema_types: 'Organization,WebSite',     status: 'published', order: 1, notes: 'Trang chủ - cần update Q2' },
      { silo_id: IDS.silo_service, silo_name: 'Dịch Vụ SEO',             silo_type: 'pillar',        parent_id: IDS.silo_home,  level: 1, target_url: '/dich-vu-seo',         primary_keyword: 'dịch vụ seo website',  secondary_keywords: 'seo onpage, seo offpage, seo kỹ thuật', search_intent: 'commercial', h1_tag: 'Dịch Vụ SEO Website Chuyên Nghiệp',           meta_title: 'Dịch Vụ SEO Website Chuyên Nghiệp | Tăng Thứ Hạng Google',      meta_description: 'Dịch vụ SEO toàn diện: onpage, offpage, technical SEO. Cam kết lên top trong 3-6 tháng.', internal_links_out: '/bang-gia-seo|/blog/seo-co-ban', internal_links_in: '/',  word_count_target: 3000, content_type: 'service', schema_types: 'Service,LocalBusiness',  status: 'published', order: 1, notes: '' },
      { silo_id: IDS.silo_blog,    silo_name: 'Blog SEO',                 silo_type: 'blog_category', parent_id: IDS.silo_home,  level: 1, target_url: '/blog',                primary_keyword: 'blog seo',             secondary_keywords: 'kiến thức seo, học seo, hướng dẫn seo', search_intent: 'informational', h1_tag: 'Blog SEO - Kiến Thức & Hướng Dẫn Thực Tiễn', meta_title: 'Blog SEO | Kiến Thức SEO Cập Nhật Mới Nhất',                    meta_description: 'Cập nhật kiến thức SEO mới nhất, hướng dẫn thực tiễn từ các chuyên gia.', internal_links_out: '/blog/seo-co-ban|/blog/cong-cu-seo', internal_links_in: '/', word_count_target: 800,  content_type: 'category', schema_types: 'Blog',                  status: 'published', order: 2, notes: '' },
      { silo_id: IDS.silo_audit,   silo_name: 'SEO Audit Checklist',      silo_type: 'cluster',       parent_id: IDS.silo_blog,  level: 2, target_url: '/blog/seo-audit',      primary_keyword: 'seo audit website',   secondary_keywords: 'kiểm tra seo, technical seo audit', search_intent: 'informational',   h1_tag: 'SEO Audit: Checklist Kiểm Tra Website Từ A-Z',  meta_title: 'SEO Audit Website: Checklist 55+ Điểm Kiểm Tra',               meta_description: 'Hướng dẫn thực hiện SEO audit website toàn diện với 55+ điểm kiểm tra kỹ thuật.', internal_links_out: '/dich-vu-seo|/blog', internal_links_in: '/blog', word_count_target: 2500, content_type: 'guide', schema_types: 'Article,HowTo',        status: 'content_brief_sent', order: 1, notes: 'Writer đang làm' },
      { silo_id: IDS.silo_pricing, silo_name: 'Bảng Giá SEO',             silo_type: 'support',       parent_id: IDS.silo_service, level: 2, target_url: '/bang-gia-seo',      primary_keyword: 'bảng giá dịch vụ seo', secondary_keywords: 'chi phí seo, giá seo tháng', search_intent: 'transactional', h1_tag: 'Bảng Giá Dịch Vụ SEO 2026 - Minh Bạch 100%',   meta_title: 'Bảng Giá Dịch Vụ SEO 2026 | Gói SEO Phù Hợp Mọi Ngân Sách',   meta_description: 'Xem bảng giá dịch vụ SEO chi tiết, minh bạch. Gói từ 5 triệu/tháng. Tư vấn miễn phí.', internal_links_out: '/dich-vu-seo|/', internal_links_in: '/dich-vu-seo', word_count_target: 1200, content_type: 'landing', schema_types: 'Service,PriceSpecification', status: 'published', order: 2, notes: 'Cập nhật giá Q2/2026' },
    ];
    SheetDB.batchAddRows(ssId, 'silo_structure', rows);
    return rows.length;
  },

  // ── M4: Content Outlines ────────────────────────────────────
  _seedOutlines(ssId, IDS, now) {
    const outlineStructure = JSON.stringify([
      { level: 'H2', heading: 'Dịch Vụ SEO Là Gì?', points: ['Định nghĩa SEO', 'Tại sao cần SEO'], keywords: ['seo là gì', 'lợi ích seo'] },
      { level: 'H2', heading: 'Các Loại SEO Chúng Tôi Cung Cấp', points: ['SEO Onpage', 'SEO Offpage', 'Technical SEO', 'Local SEO'], keywords: ['dịch vụ seo', 'seo website'] },
      { level: 'H2', heading: 'Quy Trình SEO 6 Bước', points: ['Audit - Nghiên cứu - Xây silo - Tối ưu nội dung - Xây link - Báo cáo'], keywords: ['quy trình seo'] },
      { level: 'H2', heading: 'Kết Quả Khách Hàng Đạt Được', points: ['Case study 3 khách hàng điển hình'], keywords: ['case study seo'] },
      { level: 'H2', heading: 'Câu Hỏi Thường Gặp', points: ['SEO mất bao lâu?', 'Chi phí SEO bao nhiêu?', 'Cam kết gì?'], keywords: ['faq seo'] },
    ]);

    const rows = [
      { outline_id: IDS.outline_home, silo_id: IDS.silo_home, target_url: '/', working_title: 'Dịch Vụ SEO Website Uy Tín - Tăng Organic Traffic Bền Vững', primary_keyword: 'dịch vụ seo website', secondary_keywords: 'agency seo, công ty seo uy tín', search_intent: 'commercial', target_word_count: 1500, meta_title: 'Dịch Vụ SEO Website #1 - Tăng Traffic Hữu Cơ', meta_description: 'SEO toàn diện giúp website lên top Google. Tư vấn miễn phí.', h1_tag: 'Dịch Vụ SEO Website Uy Tín #1 Việt Nam', outline_structure: outlineStructure, internal_links: '/dich-vu-seo|/bang-gia-seo', external_links: 'https://search.google.com/search-console', schema_required: 'Organization,WebSite', eeat_notes: 'Cần chứng chỉ Google partner, case study thực tế', geo_requirements: 'Thêm statistics từ GSC, FAQ schema, passages rõ ràng', tone: 'professional', competitor_urls: 'https://seongon.com|https://mcomm.com.vn', status: 'reviewed', assigned_to: 'AI/Gemini', notes: 'Chờ approve từ lead' },
      { outline_id: IDS.outline_svc, silo_id: IDS.silo_service, target_url: '/dich-vu-seo', working_title: 'Dịch Vụ SEO Website Chuyên Nghiệp - Cam Kết Lên Top 3-6 Tháng', primary_keyword: 'dịch vụ seo website', secondary_keywords: 'seo onpage, seo offpage, technical seo', search_intent: 'commercial', target_word_count: 3000, meta_title: 'Dịch Vụ SEO Website Chuyên Nghiệp | Tăng Thứ Hạng Google', meta_description: 'Dịch vụ SEO toàn diện. Cam kết lên top trong 3-6 tháng.', h1_tag: 'Dịch Vụ SEO Website Chuyên Nghiệp', outline_structure: outlineStructure, internal_links: '/bang-gia-seo|/blog/seo-co-ban', external_links: '', schema_required: 'Service,LocalBusiness', eeat_notes: 'Liệt kê kinh nghiệm 5 năm, portfolio khách hàng', geo_requirements: 'Structured data Q&A, citations từ báo', tone: 'professional', competitor_urls: '', status: 'approved', assigned_to: 'Nguyễn Văn A', notes: 'Giao writer 15/04' },
      { outline_id: IDS.outline_guide, silo_id: IDS.silo_audit, target_url: '/blog/seo-audit', working_title: 'SEO Audit Website: Checklist 55+ Điểm Kiểm Tra Từ A-Z [2026]', primary_keyword: 'seo audit website', secondary_keywords: 'kiểm tra seo, technical audit', search_intent: 'informational', target_word_count: 2500, meta_title: 'SEO Audit Website: Checklist 55+ Điểm Kiểm Tra', meta_description: 'Hướng dẫn SEO audit website toàn diện với 55+ điểm kiểm tra kỹ thuật.', h1_tag: 'SEO Audit: Checklist Kiểm Tra Website Từ A-Z', outline_structure: outlineStructure, internal_links: '/dich-vu-seo|/blog', external_links: 'https://developers.google.com/search', schema_required: 'Article,HowTo', eeat_notes: 'Cần author bio, ngày cập nhật, thống kê thực tế', geo_requirements: 'Chia nhỏ bài thành passages, dùng tiêu đề câu hỏi', tone: 'educational', competitor_urls: 'https://backlinko.com/seo-audit', status: 'draft', assigned_to: '', notes: '' },
    ];
    SheetDB.batchAddRows(ssId, 'content_outlines', rows);
    return rows.length;
  },

  // ── M5: Technical Audit ─────────────────────────────────────
  _seedTechAudit(ssId, IDS, now) {
    const rows = [
      // Crawlability
      { audit_id: uuid(), category: 'crawlability', item: 'robots.txt tồn tại và cấu hình đúng', severity: 'critical', status: 'pass',         url: '/robots.txt',   details: 'Allow: / — đúng', recommendation: '', assigned_to: '', due_date: '', fixed_date: now.split('T')[0], evidence: '', notes: '' },
      { audit_id: uuid(), category: 'crawlability', item: 'XML Sitemap tồn tại và submit Google', severity: 'critical', status: 'pass',         url: '/sitemap.xml',  details: '250 URLs in sitemap', recommendation: '', assigned_to: '', due_date: '', fixed_date: now.split('T')[0], evidence: '', notes: '' },
      { audit_id: uuid(), category: 'crawlability', item: 'Orphan pages không có internal link',  severity: 'high',     status: 'fail',         url: '',              details: 'Tìm thấy 12 orphan pages', recommendation: 'Thêm link từ category hoặc sitemap', assigned_to: 'Dev', due_date: '2026-04-20', fixed_date: '', evidence: '', notes: '12 trang chưa được link đến' },
      { audit_id: uuid(), category: 'crawlability', item: 'Crawl depth tối đa 3 click từ homepage', severity: 'medium', status: 'in_progress', url: '',              details: 'Một số trang depth 4-5', recommendation: 'Cải thiện internal link structure', assigned_to: 'SEO', due_date: '2026-04-25', fixed_date: '', evidence: '', notes: '' },
      // Indexability
      { audit_id: uuid(), category: 'indexability', item: 'Canonical tags đúng trên tất cả trang',  severity: 'critical', status: 'fail',      url: '/blog?page=2',  details: 'Paginated pages thiếu canonical', recommendation: 'Thêm rel=canonical pointing về page 1', assigned_to: 'Dev', due_date: '2026-04-15', fixed_date: '', evidence: 'https://gsc.screenshot', notes: 'Ảnh hưởng duplicate content' },
      { audit_id: uuid(), category: 'indexability', item: 'noindex không được set nhầm trên trang quan trọng', severity: 'critical', status: 'pass', url: '', details: 'Kiểm tra 50 URL chính — OK', recommendation: '', assigned_to: '', due_date: '', fixed_date: '', evidence: '', notes: '' },
      { audit_id: uuid(), category: 'indexability', item: 'Meta description không trùng lặp',        severity: 'medium', status: 'fail',        url: '',              details: '8 trang có meta description giống nhau', recommendation: 'Viết meta description riêng cho từng trang', assigned_to: 'Content', due_date: '2026-04-30', fixed_date: '', evidence: '', notes: '' },
      // Performance
      { audit_id: uuid(), category: 'performance', item: 'LCP < 2.5s trên mobile',                  severity: 'critical', status: 'fail',       url: '/',             details: 'LCP = 4.2s (PageSpeed Insights)', recommendation: 'Tối ưu Hero image, defer render-blocking CSS', assigned_to: 'Dev', due_date: '2026-04-18', fixed_date: '', evidence: 'ps.insights/report', notes: 'Core Web Vitals ảnh hưởng ranking' },
      { audit_id: uuid(), category: 'performance', item: 'CLS < 0.1',                               severity: 'high',    status: 'pass',        url: '/',             details: 'CLS = 0.04', recommendation: '', assigned_to: '', due_date: '', fixed_date: '', evidence: '', notes: '' },
      { audit_id: uuid(), category: 'performance', item: 'FID < 100ms',                             severity: 'high',    status: 'pass',        url: '/',             details: 'FID = 45ms', recommendation: '', assigned_to: '', due_date: '', fixed_date: '', evidence: '', notes: '' },
      { audit_id: uuid(), category: 'performance', item: 'Hình ảnh được compress và WebP',          severity: 'medium',  status: 'in_progress', url: '',              details: '60% images chưa convert WebP', recommendation: 'Convert sang WebP, thêm lazy loading', assigned_to: 'Dev', due_date: '2026-04-22', fixed_date: '', evidence: '', notes: '' },
      // Mobile
      { audit_id: uuid(), category: 'mobile', item: 'Responsive design hoạt động đúng',            severity: 'critical', status: 'pass',       url: '',              details: 'Mobile-friendly test passed', recommendation: '', assigned_to: '', due_date: '', fixed_date: '', evidence: '', notes: '' },
      { audit_id: uuid(), category: 'mobile', item: 'Font size tối thiểu 16px trên mobile',        severity: 'medium',  status: 'pass',        url: '',              details: 'Base font 16px', recommendation: '', assigned_to: '', due_date: '', fixed_date: '', evidence: '', notes: '' },
      { audit_id: uuid(), category: 'mobile', item: 'Touch targets tối thiểu 48x48px',             severity: 'medium',  status: 'fail',        url: '/contact',      details: 'Nút submit < 44px', recommendation: 'Tăng kích thước button lên 48x48px', assigned_to: 'Dev', due_date: '2026-04-20', fixed_date: '', evidence: '', notes: '' },
      // Security
      { audit_id: uuid(), category: 'security', item: 'HTTPS toàn bộ trang',                       severity: 'critical', status: 'pass',       url: '',              details: 'SSL A+ rating', recommendation: '', assigned_to: '', due_date: '', fixed_date: '', evidence: '', notes: '' },
      { audit_id: uuid(), category: 'security', item: 'HSTS header được cấu hình',                 severity: 'high',    status: 'pass',        url: '',              details: 'max-age=31536000', recommendation: '', assigned_to: '', due_date: '', fixed_date: '', evidence: '', notes: '' },
      // Schema
      { audit_id: uuid(), category: 'schema', item: 'Organization schema trên trang chủ',          severity: 'high',    status: 'pass',        url: '/',             details: 'JSON-LD hợp lệ', recommendation: '', assigned_to: '', due_date: '', fixed_date: '', evidence: '', notes: '' },
      { audit_id: uuid(), category: 'schema', item: 'FAQ schema trên các trang có FAQ section',    severity: 'medium',  status: 'not_checked', url: '',              details: '', recommendation: 'Thêm FAQPage schema cho /dich-vu-seo và /blog', assigned_to: 'SEO', due_date: '2026-05-01', fixed_date: '', evidence: '', notes: '' },
      // Architecture
      { audit_id: uuid(), category: 'architecture', item: 'URL structure nhất quán, không có ký tự đặc biệt', severity: 'high', status: 'pass', url: '', details: 'URLs clean, slug thuần lowercase', recommendation: '', assigned_to: '', due_date: '', fixed_date: '', evidence: '', notes: '' },
      { audit_id: uuid(), category: 'architecture', item: 'Breadcrumb navigation được implement',  severity: 'medium',  status: 'fail',        url: '',              details: 'Breadcrumb chưa có trên blog posts', recommendation: 'Thêm breadcrumb + BreadcrumbList schema', assigned_to: 'Dev', due_date: '2026-04-25', fixed_date: '', evidence: '', notes: '' },
    ];
    SheetDB.batchAddRows(ssId, 'technical_audit', rows);
    return rows.length;
  },

  // ── M6: On-page Checklist ───────────────────────────────────
  _seedOnpage(ssId, IDS, now) {
    const rows = [
      { check_id: uuid(), url: '/dich-vu-seo', silo_id: IDS.silo_service, category: 'title',   item: 'Title tag chứa primary keyword',        status: 'pass', current_value: 'Dịch Vụ SEO Website Chuyên Nghiệp | Tăng Thứ Hạng Google', recommended_value: '', notes: '58 chars — đạt' },
      { check_id: uuid(), url: '/dich-vu-seo', silo_id: IDS.silo_service, category: 'meta',    item: 'Meta description 155-160 ký tự',         status: 'fail', current_value: 'Dịch vụ SEO toàn diện.', recommended_value: 'Dịch vụ SEO toàn diện: onpage, offpage, technical SEO. Cam kết lên top trong 3-6 tháng. Tư vấn miễn phí.', notes: 'Quá ngắn — cần bổ sung' },
      { check_id: uuid(), url: '/dich-vu-seo', silo_id: IDS.silo_service, category: 'headings', item: 'Duy nhất 1 thẻ H1 trên trang',           status: 'pass', current_value: 'Dịch Vụ SEO Website Chuyên Nghiệp', recommended_value: '', notes: '' },
      { check_id: uuid(), url: '/dich-vu-seo', silo_id: IDS.silo_service, category: 'content', item: 'Keyword density 1-2%',                    status: 'pass', current_value: '1.4%', recommended_value: '', notes: '' },
      { check_id: uuid(), url: '/dich-vu-seo', silo_id: IDS.silo_service, category: 'images',  item: 'Tất cả ảnh có alt text',                 status: 'fail', current_value: '3/7 ảnh thiếu alt', recommended_value: 'Alt mô tả = primary keyword + context', notes: 'Fix ngay' },
      { check_id: uuid(), url: '/dich-vu-seo', silo_id: IDS.silo_service, category: 'links',   item: 'Internal links đến các trang liên quan',  status: 'pass', current_value: '4 internal links', recommended_value: '', notes: '' },
      { check_id: uuid(), url: '/dich-vu-seo', silo_id: IDS.silo_service, category: 'schema',  item: 'Service schema implemented',              status: 'pass', current_value: 'JSON-LD Service + LocalBusiness', recommended_value: '', notes: '' },
      { check_id: uuid(), url: '/dich-vu-seo', silo_id: IDS.silo_service, category: 'ux',      item: 'CTA rõ ràng above the fold',              status: 'pass', current_value: 'Nút "Tư Vấn Miễn Phí" hero section', recommended_value: '', notes: '' },
      // Blog post
      { check_id: uuid(), url: '/blog/seo-co-ban', silo_id: IDS.silo_blog, category: 'title',  item: 'Title tag chứa primary keyword',         status: 'pass', current_value: 'Cách Làm SEO Cho Người Mới: Hướng Dẫn A-Z [2026]', recommended_value: '', notes: '' },
      { check_id: uuid(), url: '/blog/seo-co-ban', silo_id: IDS.silo_blog, category: 'content', item: 'Nội dung ≥ 1500 words',                  status: 'fail', current_value: '980 words', recommended_value: '≥ 1800 words với examples cụ thể', notes: 'Cần expand phần Technical SEO' },
      { check_id: uuid(), url: '/blog/seo-co-ban', silo_id: IDS.silo_blog, category: 'links',   item: 'Ít nhất 2 external links uy tín',        status: 'na',   current_value: 'N/A — checking', recommended_value: 'Google Developers, Moz, Ahrefs blog', notes: '' },
    ];
    SheetDB.batchAddRows(ssId, 'onpage_checklist', rows);
    return rows.length;
  },

  // ── M7: GEO Checklist ───────────────────────────────────────
  _seedGeo(ssId, IDS, now) {
    const rows = [
      { geo_id: uuid(), url: '/dich-vu-seo',      silo_id: IDS.silo_service, category: 'technical',         item: 'llms.txt file tồn tại và cấu hình',             status: 'fail',        details: 'Chưa có llms.txt root domain', notes: 'Thêm vào sprint tới' },
      { geo_id: uuid(), url: '/dich-vu-seo',      silo_id: IDS.silo_service, category: 'technical',         item: 'ClaudeBot, GPTBot, PerplexityBot được allow',    status: 'pass',        details: 'robots.txt allow tất cả AI crawlers', notes: '' },
      { geo_id: uuid(), url: '/dich-vu-seo',      silo_id: IDS.silo_service, category: 'content_structure', item: 'Có đoạn văn định nghĩa rõ ràng đầu bài',        status: 'pass',        details: 'Paragraph đầu định nghĩa "dịch vụ SEO website là..."', notes: '' },
      { geo_id: uuid(), url: '/dich-vu-seo',      silo_id: IDS.silo_service, category: 'content_structure', item: 'Nội dung chia nhỏ thành passages citeable',      status: 'in_progress', details: 'Đang edit — 60% xong', notes: '' },
      { geo_id: uuid(), url: '/dich-vu-seo',      silo_id: IDS.silo_service, category: 'content_structure', item: 'Tiêu đề H2/H3 dạng câu hỏi (Who/What/How)',     status: 'fail',        details: 'H2 hiện tại không phải câu hỏi', notes: 'Cần rewrite H2s' },
      { geo_id: uuid(), url: '/dich-vu-seo',      silo_id: IDS.silo_service, category: 'authority',         item: 'Author bio với credentials',                     status: 'fail',        details: 'Chưa có author bio trên service page', notes: '' },
      { geo_id: uuid(), url: '/dich-vu-seo',      silo_id: IDS.silo_service, category: 'authority',         item: 'Citations từ nguồn uy tín (báo chí, research)',  status: 'pass',        details: '3 citations từ VnExpress, CafeF', notes: '' },
      { geo_id: uuid(), url: '/blog/seo-co-ban',  silo_id: IDS.silo_blog,    category: 'content_structure', item: 'FAQ section với markup schema',                  status: 'not_checked', details: '', notes: 'Kiểm tra sau khi viết xong' },
      { geo_id: uuid(), url: '/blog/seo-co-ban',  silo_id: IDS.silo_blog,    category: 'measurement',       item: 'Tracking AI-driven clicks (UTM parameters)',     status: 'fail',        details: 'Chưa setup UTM cho AI referrals', notes: 'GA4 event tracking' },
      { geo_id: uuid(), url: '/',                 silo_id: IDS.silo_home,    category: 'measurement',       item: 'Google Analytics 4 được setup với events',       status: 'pass',        details: 'GA4 + GTM active, custom events: CTA click, form submit', notes: '' },
    ];
    SheetDB.batchAddRows(ssId, 'geo_checklist', rows);
    return rows.length;
  },

  // ── M8: Backlinks ───────────────────────────────────────────
  _seedBacklinks(ssId, IDS, today, now) {
    const rows = [
      { backlink_id: uuid(), referring_domain: 'cafef.vn',         referring_url: 'https://cafef.vn/kinh-doanh/bai-pr-seo.html',  target_url: '/dich-vu-seo',  anchor_text: 'dịch vụ seo',           link_type: 'dofollow', domain_authority: 72, status: 'live',       outreach_date: '2026-03-10', live_date: '2026-03-15', contact_info: 'pr@cafef.vn',          cost: 5000000, campaign: 'Q1 PR Campaign', notes: 'DA cao, rất hiệu quả' },
      { backlink_id: uuid(), referring_domain: 'brandsvietnam.com', referring_url: 'https://brandsvietnam.com/articles/seo-2026',  target_url: '/',             anchor_text: 'agency seo uy tín',    link_type: 'dofollow', domain_authority: 61, status: 'live',       outreach_date: '2026-03-05', live_date: '2026-03-12', contact_info: 'contact@brands.vn',    cost: 2000000, campaign: 'Q1 PR Campaign', notes: '' },
      { backlink_id: uuid(), referring_domain: 'enternews.vn',     referring_url: 'https://enternews.vn/cong-nghe/seo-trend.html', target_url: '/bang-gia-seo', anchor_text: 'giá seo website',      link_type: 'dofollow', domain_authority: 55, status: 'negotiating', outreach_date: '2026-04-01', live_date: '',           contact_info: 'editor@enternews.vn',  cost: 3000000, campaign: 'Q2 Backlink Drive', notes: 'Đang thương lượng giá' },
      { backlink_id: uuid(), referring_domain: 'tapchimarketing.vn', referring_url: '',                                           target_url: '/blog',         anchor_text: 'blog seo hay',         link_type: 'dofollow', domain_authority: 48, status: 'contacted',   outreach_date: '2026-04-03', live_date: '',           contact_info: 'nguyen@tapchimarketing.vn', cost: 0, campaign: 'Guest Post',   notes: 'Chờ phản hồi' },
      { backlink_id: uuid(), referring_domain: 'doanhnghiep.vn',   referring_url: '',                                            target_url: '/dich-vu-seo',  anchor_text: 'dịch vụ seo chuyên nghiệp', link_type: 'dofollow', domain_authority: 42, status: 'prospect', outreach_date: '',           live_date: '',           contact_info: 'pr@doanhnghiep.vn',    cost: 1500000, campaign: 'Q2 Backlink Drive', notes: 'DA thấp nhưng niche relevant' },
      { backlink_id: uuid(), referring_domain: 'old-partner.vn',   referring_url: 'https://old-partner.vn/link-cũ',              target_url: '/',             anchor_text: 'click here',            link_type: 'nofollow', domain_authority: 15, status: 'lost',        outreach_date: '2025-12-01', live_date: '2025-12-05', contact_info: '',                     cost: 0,       campaign: 'Legacy',          notes: 'Site đã đóng cửa' },
      { backlink_id: uuid(), referring_domain: 'forum.webmaster.vn', referring_url: 'https://forum.webmaster.vn/threads/123',    target_url: '/blog/seo-co-ban', anchor_text: 'đọc thêm tại đây', link_type: 'ugc',      domain_authority: 35, status: 'live',       outreach_date: '',           live_date: today,        contact_info: '',                     cost: 0,       campaign: 'Organic',         notes: 'User mention tự nhiên' },
    ];
    SheetDB.batchAddRows(ssId, 'backlinks', rows);
    return rows.length;
  },

  // ── M9: Rankings Tracker ────────────────────────────────────
  _seedRankings(ssId, IDS, today) {
    const yesterday = _dateOffset(today, -1);
    const lastWeek  = _dateOffset(today, -7);

    const rows = [
      { tracking_id: uuid(), keyword: 'dịch vụ seo website',     target_url: '/dich-vu-seo',        position: 7,  previous_position: 9,  change: 2,  best_position: 5,  clicks: 142, impressions: 2840, ctr: 5.0, device: 'desktop', country: 'VN', tracked_date: today,     data_source: 'gsc_auto', featured_snippet: false, ai_overview: false, serp_features: 'sitelinks', notes: '' },
      { tracking_id: uuid(), keyword: 'dịch vụ seo website',     target_url: '/dich-vu-seo',        position: 9,  previous_position: 12, change: 3,  best_position: 5,  clicks: 98,  impressions: 2310, ctr: 4.2, device: 'mobile',  country: 'VN', tracked_date: yesterday, data_source: 'gsc_auto', featured_snippet: false, ai_overview: false, serp_features: '', notes: '' },
      { tracking_id: uuid(), keyword: 'bảng giá dịch vụ seo',   target_url: '/bang-gia-seo',       position: 5,  previous_position: 5,  change: 0,  best_position: 3,  clicks: 89,  impressions: 1560, ctr: 5.7, device: 'desktop', country: 'VN', tracked_date: today,     data_source: 'gsc_auto', featured_snippet: false, ai_overview: false, serp_features: 'featured_snippet,people_also_ask', notes: 'Giữ vững vị trí tốt' },
      { tracking_id: uuid(), keyword: 'cách làm seo cho người mới', target_url: '/blog/seo-co-ban', position: 14, previous_position: 18, change: 4,  best_position: 11, clicks: 67,  impressions: 3200, ctr: 2.1, device: 'mobile',  country: 'VN', tracked_date: today,     data_source: 'gsc_auto', featured_snippet: false, ai_overview: true,  serp_features: 'ai_overview', notes: 'Có AI Overview — monitor' },
      { tracking_id: uuid(), keyword: 'cách kiểm tra thứ hạng từ khóa miễn phí', target_url: '/blog/kiem-tra-thu-hang', position: 9, previous_position: 15, change: 6, best_position: 7, clicks: 54, impressions: 980, ctr: 5.5, device: 'desktop', country: 'VN', tracked_date: today, data_source: 'gsc_auto', featured_snippet: true, ai_overview: false, serp_features: 'featured_snippet', notes: 'Featured snippet mới đạt!' },
      { tracking_id: uuid(), keyword: 'dịch vụ seo tại hà nội',  target_url: '/seo-ha-noi',         position: 18, previous_position: 21, change: 3,  best_position: 15, clicks: 28,  impressions: 640,  ctr: 4.4, device: 'mobile',  country: 'VN', tracked_date: lastWeek,  data_source: 'csv_import', featured_snippet: false, ai_overview: false, serp_features: 'local_pack', notes: 'Local pack top 3' },
      { tracking_id: uuid(), keyword: 'so sánh agency seo hcm',  target_url: '/so-sanh-agency-seo', position: 31, previous_position: 28, change: -3, best_position: 24, clicks: 8,   impressions: 520,  ctr: 1.5, device: 'desktop', country: 'VN', tracked_date: today,     data_source: 'manual',    featured_snippet: false, ai_overview: false, serp_features: '', notes: 'Tụt hạng — check competitor' },
    ];
    SheetDB.batchAddRows(ssId, 'rankings', rows);
    return rows.length;
  },

  // ── M10: GSC Performance ────────────────────────────────────
  _seedGSCPerformance(ssId, IDS, today, now) {
    const rows = [];
    const queries = [
      { query: 'dịch vụ seo website',          page: '/dich-vu-seo',        baseClicks: 142, baseImpr: 2840, baseCtr: 5.0, basePos: 7.1 },
      { query: 'bảng giá dịch vụ seo',          page: '/bang-gia-seo',       baseClicks: 89,  baseImpr: 1560, baseCtr: 5.7, basePos: 5.3 },
      { query: 'cách làm seo cho người mới',    page: '/blog/seo-co-ban',    baseClicks: 67,  baseImpr: 3200, baseCtr: 2.1, basePos: 14.2 },
      { query: 'agency seo uy tín hcm',         page: '/dich-vu-seo',        baseClicks: 45,  baseImpr: 870,  baseCtr: 5.2, basePos: 8.9 },
      { query: 'cách kiểm tra thứ hạng từ khóa',page: '/blog/kiem-tra-thu-hang', baseClicks: 54, baseImpr: 980, baseCtr: 5.5, basePos: 9.0 },
    ];

    // 7 ngày dữ liệu gần nhất
    for (let d = 6; d >= 0; d--) {
      const date = _dateOffset(today, -d);
      queries.forEach(q => {
        const variance = 0.8 + Math.random() * 0.4;
        rows.push({
          record_id:   uuid(),
          query:       q.query,
          page:        q.page,
          clicks:      Math.round(q.baseClicks * variance),
          impressions: Math.round(q.baseImpr * variance),
          ctr:         +(q.baseCtr * variance).toFixed(2),
          position:    +(q.basePos + (Math.random() - 0.5) * 2).toFixed(1),
          country:     'VNM',
          device:      d % 2 === 0 ? 'MOBILE' : 'DESKTOP',
          date:        date,
          synced_at:   now,
        });
      });
    }
    SheetDB.batchAddRows(ssId, 'gsc_performance', rows);
    return rows.length;
  },

  // ── M10: GSC Index Status ───────────────────────────────────
  _seedGSCIndexStatus(ssId, IDS, now) {
    const rows = [
      { inspection_id: uuid(), url: 'https://example.com/',                  verdict: 'PASS',    coverage_state: 'Submitted and indexed', robotstxt_state: 'ALLOWED', indexing_state: 'INDEXING_ALLOWED', page_fetch_state: 'SUCCESSFUL', crawled_as: 'MOBILE', canonical_url: 'https://example.com/', user_canonical: 'https://example.com/', mobile_usability: 'MOBILE_FRIENDLY', mobile_issues: '[]', last_crawl_time: '2026-04-04T08:00:00Z', inspected_at: now },
      { inspection_id: uuid(), url: 'https://example.com/dich-vu-seo',       verdict: 'PASS',    coverage_state: 'Submitted and indexed', robotstxt_state: 'ALLOWED', indexing_state: 'INDEXING_ALLOWED', page_fetch_state: 'SUCCESSFUL', crawled_as: 'MOBILE', canonical_url: 'https://example.com/dich-vu-seo', user_canonical: 'https://example.com/dich-vu-seo', mobile_usability: 'MOBILE_FRIENDLY', mobile_issues: '[]', last_crawl_time: '2026-04-04T09:30:00Z', inspected_at: now },
      { inspection_id: uuid(), url: 'https://example.com/bang-gia-seo',      verdict: 'PASS',    coverage_state: 'Submitted and indexed', robotstxt_state: 'ALLOWED', indexing_state: 'INDEXING_ALLOWED', page_fetch_state: 'SUCCESSFUL', crawled_as: 'MOBILE', canonical_url: 'https://example.com/bang-gia-seo',  user_canonical: 'https://example.com/bang-gia-seo',  mobile_usability: 'MOBILE_FRIENDLY', mobile_issues: '[]', last_crawl_time: '2026-04-04T10:00:00Z', inspected_at: now },
      { inspection_id: uuid(), url: 'https://example.com/blog',              verdict: 'PASS',    coverage_state: 'Submitted and indexed', robotstxt_state: 'ALLOWED', indexing_state: 'INDEXING_ALLOWED', page_fetch_state: 'SUCCESSFUL', crawled_as: 'MOBILE', canonical_url: 'https://example.com/blog',           user_canonical: 'https://example.com/blog',           mobile_usability: 'MOBILE_FRIENDLY', mobile_issues: '[]', last_crawl_time: '2026-04-03T14:00:00Z', inspected_at: now },
      { inspection_id: uuid(), url: 'https://example.com/blog?page=2',       verdict: 'NEUTRAL', coverage_state: 'Duplicate, Google chose different canonical than user', robotstxt_state: 'ALLOWED', indexing_state: 'INDEXING_ALLOWED', page_fetch_state: 'SUCCESSFUL', crawled_as: 'MOBILE', canonical_url: 'https://example.com/blog', user_canonical: 'https://example.com/blog?page=2', mobile_usability: 'MOBILE_FRIENDLY', mobile_issues: '[]', last_crawl_time: '2026-04-02T11:00:00Z', inspected_at: now },
      { inspection_id: uuid(), url: 'https://example.com/old-page-deleted',  verdict: 'FAIL',    coverage_state: 'Not found (404)', robotstxt_state: 'ALLOWED', indexing_state: 'INDEXING_ALLOWED', page_fetch_state: 'NOT_FOUND', crawled_as: 'MOBILE', canonical_url: '', user_canonical: '', mobile_usability: '', mobile_issues: '[]', last_crawl_time: '2026-04-01T08:00:00Z', inspected_at: now },
    ];
    SheetDB.batchAddRows(ssId, 'gsc_index_status', rows);
    return rows.length;
  },

  // ── M13: Rate Card ──────────────────────────────────────────
  _seedRateCard(ssId, IDS) {
    const rows = [
      { item_id: uuid(), category: 'Technical SEO', item_name: 'Technical SEO Audit',        unit: 'project', unit_price: 5000000,  tier: 'standard', currency: 'VND', notes: 'Audit 50-100 URLs' },
      { item_id: uuid(), category: 'Technical SEO', item_name: 'Technical SEO Audit Pro',    unit: 'project', unit_price: 12000000, tier: 'expert',   currency: 'VND', notes: 'Audit toàn site, không giới hạn URLs' },
      { item_id: uuid(), category: 'On-page SEO',   item_name: 'Tối ưu On-page / trang',    unit: 'page',    unit_price: 800000,   tier: 'standard', currency: 'VND', notes: 'Bao gồm meta, headings, nội dung' },
      { item_id: uuid(), category: 'On-page SEO',   item_name: 'Tối ưu On-page Pro / trang', unit: 'page',   unit_price: 1500000,  tier: 'expert',   currency: 'VND', notes: 'Kèm schema markup + GEO optimization' },
      { item_id: uuid(), category: 'Content',       item_name: 'Viết bài chuẩn SEO',         unit: '1000 words', unit_price: 600000, tier: 'standard', currency: 'VND', notes: 'Tiếng Việt, E-E-A-T compliant' },
      { item_id: uuid(), category: 'Content',       item_name: 'Content Brief + Outline AI', unit: 'page',    unit_price: 300000,   tier: 'standard', currency: 'VND', notes: 'Bằng Gemini AI + human review' },
      { item_id: uuid(), category: 'Backlinks',     item_name: 'Guest Post DA 40-60',        unit: 'link',    unit_price: 2500000,  tier: 'standard', currency: 'VND', notes: 'Include content fee' },
      { item_id: uuid(), category: 'Backlinks',     item_name: 'PR Báo lớn (CafeF, VnExpress)', unit: 'link', unit_price: 7000000, tier: 'expert',   currency: 'VND', notes: 'DA > 65, do-follow' },
      { item_id: uuid(), category: 'Monthly Retainer', item_name: 'Gói SEO Monthly Basic',  unit: 'month',   unit_price: 8000000,  tier: 'standard', currency: 'VND', notes: '10 keywords, 4 bài blog, 2 backlinks' },
      { item_id: uuid(), category: 'Monthly Retainer', item_name: 'Gói SEO Monthly Pro',    unit: 'month',   unit_price: 20000000, tier: 'expert',   currency: 'VND', notes: '30 keywords, 8 bài blog, 6 backlinks, report tháng' },
      { item_id: uuid(), category: 'Reporting',    item_name: 'Monthly SEO Report',          unit: 'report',  unit_price: 2000000,  tier: 'standard', currency: 'VND', notes: 'Slides + data từ GSC' },
    ];
    SheetDB.batchAddRows(ssId, 'rate_card', rows);
    return rows.length;
  },

  // ── M13: Quotation ──────────────────────────────────────────
  _seedQuotation(ssId, IDS, today) {
    const breakdown = JSON.stringify([
      { category: 'Technical SEO', item: 'Technical SEO Audit',     qty: 1,  unit_price: 5000000,  total: 5000000 },
      { category: 'On-page SEO',   item: 'Tối ưu On-page',          qty: 10, unit_price: 800000,   total: 8000000 },
      { category: 'Content',       item: 'Viết bài chuẩn SEO',      qty: 4,  unit_price: 600000,   total: 2400000 },
      { category: 'Monthly Retainer', item: 'Gói SEO Monthly Basic', qty: 3, unit_price: 8000000,  total: 24000000 },
    ]);

    const rows = [
      { quote_id: uuid(), created_date: today, version: 1, status: 'approved', total_amount: 39400000, currency: 'VND', breakdown, notes: 'Báo giá Q1/2026 — đã ký hợp đồng' },
      { quote_id: uuid(), created_date: today, version: 2, status: 'sent',     total_amount: 48000000, currency: 'VND', breakdown: JSON.stringify([{ category: 'Monthly Retainer', item: 'Gói SEO Monthly Pro', qty: 2, unit_price: 20000000, total: 40000000 }, { category: 'Reporting', item: 'Monthly SEO Report', qty: 4, unit_price: 2000000, total: 8000000 }]), notes: 'Đề xuất upgrade lên Pro package Q2' },
      { quote_id: uuid(), created_date: today, version: 1, status: 'draft',    total_amount: 12000000, currency: 'VND', breakdown: JSON.stringify([{ category: 'Technical SEO', item: 'Technical SEO Audit Pro', qty: 1, unit_price: 12000000, total: 12000000 }]), notes: 'Audit one-off — chờ confirm' },
    ];
    SheetDB.batchAddRows(ssId, 'quotation', rows);
    return rows.length;
  },
};

/** Helper: tính ngày offset từ YYYY-MM-DD */
function _dateOffset(dateStr, offsetDays) {
  const d = new Date(dateStr);
  d.setDate(d.getDate() + offsetDays);
  return Utilities.formatDate(d, 'Asia/Ho_Chi_Minh', 'yyyy-MM-dd');
}
