document.getElementById('menuToggle')?.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('open'));
document.querySelectorAll('[data-confirm]').forEach(b=>b.addEventListener('click',e=>{if(!confirm(b.dataset.confirm))e.preventDefault()}));

const menuLinks=[...document.querySelectorAll('.sidebar a[href]')];
const currentPath=window.location.pathname;
let activeLink=menuLinks.find(link=>new URL(link.href).pathname===currentPath);
if(!activeLink){
  const parentMenu=[
    ['/inventory/product_form.php','/inventory/index.php'],
    ['/equipment/add.php','/equipment/index.php'], ['/equipment/edit.php','/equipment/index.php'], ['/equipment/view.php','/equipment/index.php'],
    ['/maintenance/add_request.php','/maintenance/requests.php'], ['/maintenance/update_request.php','/maintenance/requests.php'],
    ['/maintenance/add_work_order.php','/maintenance/work_orders.php'], ['/maintenance/update_work_order.php','/maintenance/work_orders.php']
  ].find(([child])=>currentPath.endsWith(child));
  if(parentMenu)activeLink=menuLinks.find(link=>new URL(link.href).pathname.endsWith(parentMenu[1]));
}
activeLink?.classList.add('active');
