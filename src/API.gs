// API.gs — Single action dispatcher
// MỌI gọi từ frontend đều đi qua đây: dispatch(action, params)
// KHÔNG chứa business logic — chỉ route đến Service tương ứng

/**
 * Entry point duy nhất cho tất cả client requests
 * @param {string} action — dạng 'module.method' (e.g. 'project.getAll')
 * @param {Object} params — tham số truyền vào handler
 * @returns {{ success: boolean, data?: *, error?: string }}
 */
function dispatch(action, params) {
  const handlers = {
    // ── Project Management (M1) ──────────────────────────────
    'project.getAll':       () => ProjectService.getAll(),
    'project.create':       () => ProjectService.create(params.data),
    'project.update':       () => ProjectService.update(params.projectId, params.data),
    'project.delete':       () => ProjectService.delete(params.projectId),
    'project.archive':      () => ProjectService.archive(params.projectId),
    'project.duplicate':    () => ProjectService.duplicate(params.projectId),

    // ── Keyword Research (M2) ────────────────────────────────
    'keyword.getAll':       () => KeywordService.getAll(params.projectId),
    'keyword.add':          () => KeywordService.add(params.projectId, params.data),
    'keyword.bulkAdd':      () => KeywordService.bulkAdd(params.projectId, params.rows),
    'keyword.update':       () => KeywordService.update(params.projectId, params.keywordId, params.data),
    'keyword.delete':       () => KeywordService.delete(params.projectId, params.keywordId),
    'keyword.classify':     () => KeywordService.classifyIntent(params.keywords),
    'keyword.applyIntents': () => KeywordService.applyIntents(params.projectId, params.updates),

    // ── Silo Architecture (M3) ───────────────────────────────
    'silo.getTree':         () => SiloService.getTree(params.projectId),
    'silo.add':             () => SiloService.add(params.projectId, params.data),
    'silo.update':          () => SiloService.update(params.projectId, params.siloId, params.data),
    'silo.delete':          () => SiloService.delete(params.projectId, params.siloId),
    'silo.reorder':         () => SiloService.reorder(params.projectId, params.updates),

    // ── Content Outline (M4) ─────────────────────────────────
    'outline.getAll':       () => OutlineService.getAll(params.projectId),
    'outline.get':          () => OutlineService.get(params.projectId, params.outlineId),
    'outline.create':       () => OutlineService.create(params.projectId, params.data),
    'outline.update':       () => OutlineService.update(params.projectId, params.outlineId, params.data),
    'outline.delete':       () => OutlineService.delete(params.projectId, params.outlineId),
    'outline.generate':     () => OutlineService.generateWithAI(params.projectId, params.siloId),
    'outline.exportDoc':    () => OutlineService.exportGoogleDoc(params.projectId, params.outlineId),

    // ── Technical Audit (M5) ─────────────────────────────────
    'audit.getChecklist':   () => AuditService.getChecklist(params.projectId),
    'audit.updateItem':     () => AuditService.updateItem(params.projectId, params.itemId, params.data),
    'audit.reset':          () => AuditService.reset(params.projectId),
    'audit.getStats':       () => AuditService.getStats(params.projectId),

    // ── GSC Integration (M10) ───────────────────────────────
    'gsc.listProperties':   () => GSCService.listProperties(),
    'gsc.syncPerformance':  () => GSCService.syncPerformance(params.projectId),
    'gsc.inspectUrls':      () => GSCService.inspectUrls(params.projectId),
    'gsc.getStats':         () => GSCService.getStats(params.projectId),

    // ── Dashboard (M11) ─────────────────────────────────────
    'dashboard.getStats':   () => DashboardService.getStats(params.projectId),

    // ── Reporting (M12) ─────────────────────────────────────
    'report.generate':      () => ReportService.generate(params.projectId, params.options),

    // ── Setup / Config ──────────────────────────────────────
    'setup.getConfig':      () => SetupService.getConfig(),
    'setup.saveConfig':     () => SetupService.saveConfig(params.data),
    'setup.initTriggers':   () => SetupService.initTriggers(),
    'setup.testMasterSheetConnection': () => SetupService.testMasterSheetConnection(params.spreadsheetId),

    // ── Seed (Dev only) ─────────────────────────────────────
    'seed.run':             () => SeedService.run(params.projectId),
  };

  const handler = handlers[action];
  if (!handler) {
    Logger.log(`[API] ERROR: Unknown action '${action}'`);
    return { success: false, error: `Unknown action: ${action}` };
  }

  try {
    const data = handler();
    return { success: true, data: data ?? null };
  } catch (e) {
    Logger.log(`[API] ERROR in '${action}': ${e.message}`);
    return { success: false, error: e.message };
  }
}
