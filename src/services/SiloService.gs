// services/SiloService.gs — M3: Silo Architecture & URL Structure
// Quản lý cây silo: tree build, CRUD nodes, reorder

const SiloService = {
  /**
   * Lấy flat list rows từ sheet, buid thành tree structure
   */
  getTree(projectId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const rows = getWithCache(
      cacheKey('silo_tree', projectId),
      () => SheetDB.getRows(ssId, 'silo_structure'),
      CACHE_TTL.silo_tree
    );
    return SiloService._buildTree(rows);
  },

  /**
   * Build nested tree từ flat array
   * @param {Object[]} rows
   * @returns {Object[]} — top-level nodes với children[]
   */
  _buildTree(rows) {
    const map = {};
    rows.forEach(r => { map[r.silo_id] = { ...r, children: [] }; });
    const roots = [];
    rows.forEach(r => {
      if (r.parent_id && map[r.parent_id]) {
        map[r.parent_id].children.push(map[r.silo_id]);
      } else {
        roots.push(map[r.silo_id]);
      }
    });
    return roots;
  },

  /**
   * Thêm node silo mới
   */
  add(projectId, data) {
    validateRequired(data, ['page_title', 'slug']);
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const row = {
      silo_id: uuid(),
      parent_id: data.parent_id || '',
      level: data.level || 1,
      page_title: data.page_title.trim(),
      slug: data.slug.trim(),
      meta_title: data.meta_title || '',
      meta_description: data.meta_description || '',
      status: data.status || 'draft',
      index_status: '',
      internal_links_in: 0,
      internal_links_out: 0,
      created_at: nowISO()
    };
    SheetDB.addRow(ssId, 'silo_structure', row);
    invalidateCache([cacheKey('silo_tree', projectId)]);
    return row;
  },

  /**
   * Cập nhật node silo
   */
  update(projectId, siloId, data) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const ok = SheetDB.updateRow(ssId, 'silo_structure', 'silo_id', siloId, data);
    if (!ok) throw new Error(`Silo node not found: ${siloId}`);
    invalidateCache([cacheKey('silo_tree', projectId)]);
    return { silo_id: siloId, ...data };
  },

  /**
   * Xoá node silo (không cascade — frontend phải check children trước)
   */
  delete(projectId, siloId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const ok = SheetDB.deleteRow(ssId, 'silo_structure', 'silo_id', siloId);
    if (!ok) throw new Error(`Silo node not found: ${siloId}`);
    invalidateCache([cacheKey('silo_tree', projectId)]);
    return true;
  },

  /**
   * Batch reorder — cập nhật parent_id + level cho nhiều nodes
   * @param {Object[]} updates — [{ silo_id, parent_id, level }]
   */
  reorder(projectId, updates) {
    if (!updates || updates.length === 0) throw new Error('updates array is required');
    const ssId = ProjectService.getSpreadsheetId(projectId);
    let count = 0;
    updates.forEach(({ silo_id, parent_id, level }) => {
      if (SheetDB.updateRow(ssId, 'silo_structure', 'silo_id', silo_id, { parent_id, level })) count++;
    });
    invalidateCache([cacheKey('silo_tree', projectId)]);
    return { updated: count };
  }
};
