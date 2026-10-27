(() => {
  'use strict';
  const dataNode = document.getElementById('project-data');
  if (!dataNode) return;
  const data = JSON.parse(dataNode.textContent);
  const projects = new Map(data.projects.map(project => [String(project.id), project]));
  const editor = document.getElementById('editor-dialog');
  const form = document.getElementById('editor-form');
  const deletion = document.getElementById('delete-dialog');
  const deleteForm = document.getElementById('delete-form');
  let lastTrigger = null;

  function open(dialog, trigger) {
    lastTrigger = trigger || document.activeElement;
    document.documentElement.classList.add('has-modal');
    dialog.showModal();
  }
  function openEditor(project, trigger) {
    form.reset();
    const editing = project && project.action !== 'add';
    form.elements.action.value = editing ? 'edit' : 'add';
    form.elements.id.value = editing ? project.id : '';
    for (const name of ['titre', 'description', 'lien_projet', 'technologie']) form.elements[name].value = project?.[name] || '';
    form.elements.ordre.value = project?.ordre ?? data.nextOrder;
    form.elements.espace_admin.checked = Number(project?.espace_admin) === 1;
    document.getElementById('editor-title').textContent = editing ? 'Modifier le projet' : 'Ajouter un projet';
    document.getElementById('editor-intro').textContent = editing ? 'Mettez à jour sa présentation sur votre accueil.' : 'Présentez votre prochain projet sur votre accueil.';
    document.getElementById('save-label').textContent = editing ? 'Enregistrer les modifications' : 'Ajouter le projet';
    open(editor, trigger);
    form.elements.titre.focus({ preventScroll: true });
  }
  document.querySelectorAll('[data-add]').forEach(button => button.addEventListener('click', () => openEditor(null, button)));
  document.querySelectorAll('[data-edit]').forEach(button => button.addEventListener('click', () => {
    const project = projects.get(button.dataset.edit);
    if (project) openEditor(project, button);
  }));
  document.querySelectorAll('[data-delete]').forEach(button => button.addEventListener('click', () => {
    const project = projects.get(button.dataset.delete);
    if (!project) return;
    deleteForm.elements.id.value = project.id;
    document.getElementById('delete-project-name').textContent = project.titre;
    open(deletion, button);
    deletion.querySelector('[data-close][autofocus]').focus({ preventScroll: true });
  }));
  for (const dialog of [editor, deletion]) {
    dialog.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => {
      document.documentElement.classList.remove('has-modal');
      if (lastTrigger?.isConnected) lastTrigger.focus({ preventScroll: true });
    });
    dialog.addEventListener('click', event => {
      if (event.target !== dialog) return;
      const bounds = dialog.getBoundingClientRect();
      if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
  }
  const rows = [...document.querySelectorAll('[data-project-row]')];
  const search = document.getElementById('project-search');
  const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('fr');
  search.addEventListener('input', () => {
    const term = normalize(search.value.trim());
    let visible = 0;
    for (const row of rows) {
      row.hidden = !normalize(row.dataset.search).includes(term);
      if (!row.hidden) visible++;
    }
    document.getElementById('search-empty').hidden = visible !== 0;
    document.getElementById('result-count').textContent = `${visible} projet${visible > 1 ? 's' : ''}${term ? ` sur ${rows.length}` : ''}`;
  });
  for (const postForm of [form, deleteForm]) {
    postForm.addEventListener('submit', () => {
      postForm.setAttribute('aria-busy', 'true');
      postForm.querySelector('button[type="submit"]').disabled = true;
    });
  }
  if (data.reopen) openEditor(data.reopen);
})();
