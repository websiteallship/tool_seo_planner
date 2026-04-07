// services/AuditService.gs — M5: Technical Audit Checklist
// Load template 55+ items, track progress, AI summarize

const AuditService = {
  /**
   * Lấy tất cả audit items của project (load từ sheet, nếu rỗng thì seed từ template)
   */
  getChecklist(projectId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const items = SheetDB.getRows(ssId, 'technical_audit');
    if (items.length === 0) {
      // Chưa có checklist — seed từ template
      return AuditService._seedFromTemplate(projectId, ssId);
    }
    return items;
  },

  /**
   * Seed checklist từ JSON template file
   */
  _seedFromTemplate(projectId, ssId) {
    const templateJson = HtmlService.createHtmlOutputFromFile('templates/technical_audit_checklist').getContent();
    const template = JSON.parse(templateJson);
    const now = nowISO();
    const rows = template.items.map(item => ({
      item_id: uuid(),
      template_id: item.id,
      category: item.category,
      severity: item.severity,
      title: item.title,
      description: item.description,
      status: 'pending',
      assignee: '',
      due_date: '',
      evidence_url: '',
      notes: '',
      checked_at: ''
    }));
    SheetDB.batchAddRows(ssId, 'technical_audit', rows);
    Logger.log(`[AuditService] INFO: Seeded ${rows.length} audit items for project ${projectId}`);
    return rows;
  },

  /**
   * Cập nhật 1 audit item (status, assignee, evidence...)
   */
  updateItem(projectId, itemId, data) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const updates = {
      ...data,
      checked_at: data.status !== 'pending' ? nowISO() : ''
    };
    const ok = SheetDB.updateRow(ssId, 'technical_audit', 'item_id', itemId, updates);
    if (!ok) throw new Error(`Audit item not found: ${itemId}`);
    return { item_id: itemId, ...updates };
  },

  /**
   * Reset: set tất cả items về pending (re-audit)
   */
  reset(projectId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const items = SheetDB.getRows(ssId, 'technical_audit');
    let count = 0;
    items.forEach(item => {
      SheetDB.updateRow(ssId, 'technical_audit', 'item_id', item.item_id, {
        status: 'pending', assignee: '', due_date: '', evidence_url: '', notes: '', checked_at: ''
      });
      count++;
    });
    return { reset: count };
  },

  /**
   * Stats: % hoàn thành theo category và severity
   */
  getStats(projectId) {
    const items = AuditService.getChecklist(projectId);
    const total = items.length;
    const passed = items.filter(i => i.status === 'pass').length;
    const failed = items.filter(i => i.status === 'fail').length;
    const pending = items.filter(i => i.status === 'pending').length;
    const na = items.filter(i => i.status === 'na').length;

    const byCategory = {};
    const bySeverity = { critical: 0, high: 0, medium: 0, low: 0 };
    items.forEach(i => {
      if (!byCategory[i.category]) byCategory[i.category] = { total: 0, passed: 0 };
      byCategory[i.category].total++;
      if (i.status === 'pass') byCategory[i.category].passed++;
      if (i.severity in bySeverity) bySeverity[i.severity]++;
    });

    return { total, passed, failed, pending, na, completion: Math.round((passed + na) / total * 100), byCategory, bySeverity };
  }
};
