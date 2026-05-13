(function(){
  function handleDelete(e){
    e.preventDefault();
    const form = e.currentTarget;
    if (!confirm('Delete?')) return;
    const row = form.closest('tr');
    const action = form.action;
    const data = new FormData(form);

    // optimistic removal: clone row to restore on failure
    const parent = row.parentNode;
    const nextSibling = row.nextSibling;
    const backup = row.cloneNode(true);

    // animate collapse immediately
    row.style.transition = 'opacity .28s ease, transform .28s ease, height .28s ease, margin .28s ease, padding .28s ease';
    const h = row.offsetHeight + 'px';
    row.style.height = h;
    requestAnimationFrame(()=>{
      row.classList.add('ajax-deleting');
      row.style.height = '0px';
      row.style.padding = '0px';
      row.style.margin = '0px';
    });
    setTimeout(()=>{ if (row.parentNode) row.remove(); }, 350);

    fetch(action, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(resp => {
        if (!resp.ok) throw new Error('Delete failed');
        return resp.json().catch(() => ({}));
      })
      .then(() => {
        if (typeof showToast === 'function') showToast('Deleted', 'success');
      })
      .catch(()=>{
        // restore backup row
        if (nextSibling) parent.insertBefore(backup, nextSibling); else parent.appendChild(backup);
        if (typeof showToast === 'function') showToast('Unable to delete.', 'error'); else alert('Unable to delete.');
      });
  }

  function attach(){
    document.querySelectorAll('form.ajax-delete').forEach(f=>{
      f.removeEventListener('submit', handleDelete);
      f.addEventListener('submit', handleDelete);
    });
  }

  // attach on DOM ready
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', attach);
  else attach();

  // re-attach if needed when AJAX updates could re-insert forms
  window.adminDeleteAttach = attach;
})();
