function setupTabs(rootId){
  const root = document.getElementById(rootId);
  if(!root) return;
  const buttons = root.querySelectorAll('[data-tab]');
  const panels = root.querySelectorAll('[data-panel]');
  buttons.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const name = btn.getAttribute('data-tab');
      buttons.forEach(b=>b.classList.toggle('active', b===btn));
      panels.forEach(p=>p.hidden = (p.getAttribute('data-panel')!==name));
    });
  });
}
document.addEventListener('DOMContentLoaded', ()=>{
  setupTabs('right-tabs');
});