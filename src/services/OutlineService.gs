// services/OutlineService.gs — M4: Content Outline Generator
// Quản lý content outlines: CRUD, AI generate, export Google Doc

const OutlineService = {
  getAll(projectId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const rows = SheetDB.getRows(ssId, 'content_outlines');
    // Parse content_json từ string
    return rows.map(r => ({
      ...r,
      content_json: r.content_json ? JSON.parse(r.content_json) : null
    }));
  },

  get(projectId, outlineId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const row = SheetDB.findOne(ssId, 'content_outlines', 'outline_id', outlineId);
    if (!row) throw new Error(`Outline not found: ${outlineId}`);
    return { ...row, content_json: row.content_json ? JSON.parse(row.content_json) : null };
  },

  create(projectId, data) {
    validateRequired(data, ['title']);
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const now = nowISO();
    const row = {
      outline_id: uuid(),
      silo_id: data.silo_id || '',
      keyword_id: data.keyword_id || '',
      title: data.title.trim(),
      status: 'draft',
      content_json: data.content_json ? JSON.stringify(data.content_json) : '',
      doc_url: '',
      assignee: data.assignee || '',
      due_date: data.due_date || '',
      created_at: now,
      updated_at: now
    };
    SheetDB.addRow(ssId, 'content_outlines', row);
    return { ...row, content_json: data.content_json || null };
  },

  update(projectId, outlineId, data) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const updates = { ...data, updated_at: nowISO() };
    // Serialize content_json nếu là object
    if (updates.content_json && typeof updates.content_json === 'object') {
      updates.content_json = JSON.stringify(updates.content_json);
    }
    const ok = SheetDB.updateRow(ssId, 'content_outlines', 'outline_id', outlineId, updates);
    if (!ok) throw new Error(`Outline not found: ${outlineId}`);
    return { outline_id: outlineId, ...updates };
  },

  delete(projectId, outlineId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const ok = SheetDB.deleteRow(ssId, 'content_outlines', 'outline_id', outlineId);
    if (!ok) throw new Error(`Outline not found: ${outlineId}`);
    return true;
  },

  /**
   * AI Generate Outline — Gemini tạo outline từ silo node + keywords
   * KHÔNG lưu trực tiếp — trả về draft để user review
   */
  generateWithAI(projectId, siloId) {
    const silo = SheetDB.findOne(
      ProjectService.getSpreadsheetId(projectId),
      'silo_structure', 'silo_id', siloId
    );
    if (!silo) throw new Error(`Silo not found: ${siloId}`);

    // Lấy keywords liên quan
    const keywords = KeywordService.getAll(projectId).filter(k => k.silo_id === siloId);

    const schema = {
      type: 'OBJECT',
      properties: {
        title:       { type: 'STRING' },
        meta_title:  { type: 'STRING' },
        meta_desc:   { type: 'STRING' },
        headings: {
          type: 'ARRAY',
          items: {
            type: 'OBJECT',
            properties: {
              level:    { type: 'INTEGER' },
              text:     { type: 'STRING' },
              notes:    { type: 'STRING' }
            }
          }
        }
      }
    };

    const prompt = `Generate an SEO content outline for this page:
Page title: ${silo.page_title}
Target keywords: ${keywords.slice(0, 10).map(k => k.keyword).join(', ')}
Create a comprehensive H1-H4 outline following SEO best practices.`;

    const result = callGeminiSafe(prompt, schema);
    if (!result) throw new Error('AI outline generation failed');
    return result;
  },

  /**
   * Export outline thành Google Doc
   */
  exportGoogleDoc(projectId, outlineId) {
    const outline = OutlineService.get(projectId, outlineId);
    if (!outline.content_json) throw new Error('Outline has no content to export');

    // Tạo Google Doc
    const doc = DocumentApp.create(`Outline: ${outline.title}`);
    const body = doc.getBody();
    body.appendParagraph(outline.title).setHeading(DocumentApp.ParagraphHeading.HEADING1);

    if (outline.content_json && outline.content_json.headings) {
      outline.content_json.headings.forEach(h => {
        const style = [
          DocumentApp.ParagraphHeading.HEADING1,
          DocumentApp.ParagraphHeading.HEADING2,
          DocumentApp.ParagraphHeading.HEADING3,
          DocumentApp.ParagraphHeading.HEADING4
        ][Math.min(h.level - 1, 3)];
        body.appendParagraph(h.text).setHeading(style);
        if (h.notes) body.appendParagraph(`Notes: ${h.notes}`);
      });
    }

    doc.saveAndClose();
    const docUrl = `https://docs.google.com/document/d/${doc.getId()}`;

    // Lưu doc_url vào outline
    OutlineService.update(projectId, outlineId, { doc_url: docUrl, status: 'sent' });
    return { doc_url: docUrl };
  }
};
