<?php
$modulos = [
    ['nombre'=>'Productos','descripcion'=>'Registra, busca, edita y elimina los productos de tu negocio.','icono'=>'▣','ruta'=>'productos/index.php','categoria'=>'Administración','tono'=>'lila'],
    ['nombre'=>'Categorías','descripcion'=>'Organiza los productos por categorías.','icono'=>'▰','ruta'=>'categorias/index.php','categoria'=>'Administración','tono'=>'verde'],
    ['nombre'=>'Marcas','descripcion'=>'Administra las marcas de los productos.','icono'=>'◇','ruta'=>'marcas/index.php','categoria'=>'Administración','tono'=>'rosa'],
    ['nombre'=>'Inventario','descripcion'=>'Controla las existencias y los niveles de stock.','icono'=>'▥','ruta'=>'inventario/index.php','categoria'=>'Administración','tono'=>'azul'],
    ['nombre'=>'Proveedores','descripcion'=>'Gestiona los datos de tus proveedores.','icono'=>'▱','ruta'=>'proveedores/index.php','categoria'=>'Administración','tono'=>'lila'],
    ['nombre'=>'Consultas y Estadísticas','descripcion'=>'Consulta precios, productos más vendidos, inventario bajo y ventas del día.','icono'=>'↗','ruta'=>'consultas/index.php','categoria'=>'Reportes','tono'=>'rosa']
];

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Punto de Venta | Panel principal</title>
<style>
:root{--ink:#30204b;--muted:#81778f;--purple:#7851a9;--purple-dark:#4d326f;--bg:#f4edf3;--line:#eee4ed;--white:#fffdfa}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,"Segoe UI",Arial,sans-serif;color:var(--ink);background:linear-gradient(135deg,#e9dce8 0%,#f7eef0 50%,#e9dfe9 100%);min-height:100vh;padding:24px}
a{color:inherit}
.app{max-width:1600px;min-height:calc(100vh - 48px);margin:auto;background:var(--white);border:1px solid rgba(255,255,255,.8);border-radius:22px;box-shadow:0 18px 55px #6c52731b;display:grid;grid-template-columns:265px minmax(0,1fr) 300px;overflow:hidden}
.sidebar{background:#fbf5f0;border-right:1px solid #eee5e9;padding:26px 18px;display:flex;flex-direction:column;min-width:0}
.brand{display:flex;gap:13px;align-items:center;padding:0 10px 30px}.brand-icon{height:50px;width:50px;border-radius:14px;background:linear-gradient(145deg,#9472b5,#684287);color:white;display:grid;place-items:center;font-size:25px;box-shadow:0 7px 16px #6b47852b}.brand-name{font-size:19px;font-weight:800;letter-spacing:-.4px}.brand-sub{font-size:11px;color:var(--muted);margin-top:5px;line-height:1.4}
.nav-label{font-size:10px;letter-spacing:1.4px;text-transform:uppercase;color:#a49aaa;font-weight:800;padding:10px 13px}.nav{display:flex;flex-direction:column;gap:6px}.nav a{display:flex;align-items:center;gap:13px;text-decoration:none;color:#5c4b6b;padding:12px 13px;border-radius:11px;font-size:14px;transition:.2s}.nav a:hover,.nav a.active{background:#e9dfea;color:#4f316f}.nav a.active{font-weight:700}.nav-icon{width:23px;text-align:center;font-size:20px;color:#73528d}.sidebar-bottom{margin-top:auto;padding:18px 12px 2px;border-top:1px solid #e9dfe5;font-size:11px;color:var(--muted);line-height:2}.online-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#399778;margin-right:7px}
.main{padding:30px 30px 24px;min-width:0}.topbar{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:29px}.welcome h1{font-size:30px;letter-spacing:-1px;margin-bottom:7px}.welcome p{font-size:14px;color:var(--muted)}.search{width:min(360px,48%);position:relative}.search span{position:absolute;left:15px;top:11px;color:#91859c;font-size:19px}.search input{width:100%;border:1px solid #e7dce9;background:#f7f0f5;border-radius:25px;padding:13px 16px 13px 43px;color:var(--ink);outline:none;font-size:12px}.search input:focus{border-color:#a78ac0;box-shadow:0 0 0 3px #a78ac01b}
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:30px}.stat{border:1px solid #eae1eb;border-radius:15px;padding:17px 17px 16px;min-width:0}.stat.lila,.module.lila{background:#f3eaf3}.stat.verde,.module.verde{background:#edf2ed}.stat.rosa,.module.rosa{background:#f8e9ed}.stat.azul,.module.azul{background:#eaf0f3}.stat-icon,.module-icon{width:45px;height:45px;border-radius:50%;display:grid;place-items:center;font-size:23px;margin-bottom:12px;background:#e4d4ef;color:#68488a}.verde .stat-icon,.verde .module-icon{background:#d4e5dc;color:#38796a}.rosa .stat-icon,.rosa .module-icon{background:#f0cbd6;color:#a64d70}.azul .stat-icon,.azul .module-icon{background:#d0e0e8;color:#3e7187}.stat-label{font-size:13px;margin-bottom:9px}.stat-number{font-size:29px;letter-spacing:-.8px;font-weight:800}.stat-foot{font-size:10px;color:#8b8190;margin-top:9px;white-space:nowrap}.up{color:#34886b;font-weight:800;margin-right:5px}
.section-head{display:flex;align-items:end;justify-content:space-between;gap:12px;margin-bottom:17px}.section-head h2{font-size:21px;letter-spacing:-.5px}.section-head p{font-size:13px;color:var(--muted);margin-top:5px}.module-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}.module{border:1px solid #e9e0e9;border-radius:14px;padding:18px 19px;min-height:190px;display:flex;flex-direction:column;align-items:flex-start;transition:transform .18s,box-shadow .18s}.module:hover{transform:translateY(-3px);box-shadow:0 10px 25px #55416412}.module-icon{width:56px;height:56px;font-size:26px;margin-bottom:12px}.module h3{font-size:16px;margin-bottom:8px}.module p{font-size:12px;line-height:1.6;color:#81758d;margin-bottom:15px;flex:1}.module-bottom{width:100%;display:flex;align-items:center;justify-content:space-between;gap:8px}.module-category{font-size:10px;color:#8a7e91}.open{height:34px;width:34px;display:grid;place-items:center;text-decoration:none;border-radius:50%;background:#e6d8ef;color:#68468a;font-size:21px;transition:.2s}.verde .open{background:#dbe8df;color:#3f786c}.rosa .open{background:#f0d5df;color:#a64d70}.azul .open{background:#dce7eb;color:#47798d}.open:hover{transform:translateX(3px)}.empty{display:none;padding:20px;color:var(--muted);font-size:13px}
.right-panel{background:#fbf5f1;border-left:1px solid #eee5e9;padding:25px 18px;min-width:0}.userbar{display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-bottom:27px}.bell{font-size:19px;color:#74518c;padding-right:12px;border-right:1px solid #e4d9e3}.avatar{height:40px;width:40px;border-radius:50%;background:#8562a1;color:white;display:grid;place-items:center;font-size:17px}.user-name{font-size:12px;font-weight:700}.user-role{font-size:10px;color:var(--muted);margin-top:4px}.panel-card{background:#f7eef0;border:1px solid #eee0e8;border-radius:16px;padding:20px 17px;margin-bottom:16px}.panel-card h2{font-size:17px;margin-bottom:6px}.panel-card .sub{font-size:12px;color:var(--muted);line-height:1.5;margin-bottom:23px}.metric{display:flex;align-items:center;gap:13px;margin:18px 0 23px}.ring{width:72px;height:72px;border-radius:50%;display:grid;place-items:center;flex-shrink:0;position:relative;background:conic-gradient(#8056a5 0 78%,#e6d8e7 78% 100%)}.ring:after{content:"";position:absolute;inset:7px;border-radius:50%;background:#f7eef0}.ring.pink{background:conic-gradient(#cb7392 0 22%,#eed6df 22% 100%)}.ring.green{background:conic-gradient(#438d7d 0 42%,#d6e7e0 42% 100%)}.metric-info{min-width:0}.metric-title{font-size:11px;font-weight:700;margin-bottom:8px}.metric-value{font-size:25px;font-weight:800;letter-spacing:-.5px}.metric-caption{font-size:10px;color:var(--muted);margin-top:5px;line-height:1.4}.illustration{height:145px;border-radius:50% 45% 42% 48%;background:linear-gradient(135deg,#e9d9ed,#f2e5ee);display:flex;align-items:center;justify-content:center;margin:20px 0 18px;overflow:hidden}.bars{height:85px;display:flex;align-items:flex-end;gap:8px;border-bottom:2px solid #a98bb9;padding:0 14px;position:relative}.bars i{display:block;width:19px;border-radius:4px 4px 0 0;background:#b79ac9}.bars i:nth-child(1){height:25px}.bars i:nth-child(2){height:42px}.bars i:nth-child(3){height:57px}.bars i:nth-child(4){height:76px}.trend{position:absolute;font-size:48px;top:-37px;right:3px;color:#8a68a7;transform:rotate(-13deg)}.notice{background:#f1e8f0;border:1px solid #e8dce8;border-radius:13px;padding:14px;display:flex;gap:10px;font-size:11px;line-height:1.5}.notice b{display:block;margin-bottom:3px}.notice span{font-size:19px;color:#8a6aa2}
footer{margin-top:27px;padding-top:17px;border-top:1px solid #eee5e9;font-size:11px;color:#9b90a0;text-align:center}
@media(max-width:1250px){.app{grid-template-columns:230px minmax(0,1fr)}.right-panel{display:none}.main{padding:28px}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.module-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:720px){body{padding:10px}.app{min-height:calc(100vh - 20px);grid-template-columns:1fr;border-radius:16px}.sidebar{border-right:0;border-bottom:1px solid #eee5e9;padding:17px}.brand{padding-bottom:14px}.nav-label{display:none}.nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:5px}.nav a{font-size:12px;padding:10px 8px}.sidebar-bottom{margin-top:14px;padding-top:10px}.main{padding:22px 16px}.topbar{align-items:flex-start;flex-direction:column}.welcome h1{font-size:25px}.search{width:100%}.stats{gap:10px}.stat{padding:14px}.stat-number{font-size:25px}.stat-foot{white-space:normal;line-height:1.5}.module-grid{grid-template-columns:1fr}.module{min-height:170px}}
</style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">🛒</div>
            <div><div class="brand-name">Punto de Venta</div><div class="brand-sub">Sistema de gestión comercial</div></div>
        </div>
        <div class="nav-label">Principal</div>
        <nav class="nav" aria-label="Navegación principal">
            <a class="active" href="index.php"><span class="nav-icon">⌂</span>Inicio</a>
            <div class="nav-label" style="grid-column:1/-1">Administración</div>
            <?php foreach ($modulos as $modulo): ?>
                <?php if ($modulo['categoria'] === 'Administración'): ?>
                <a href="<?= e($modulo['ruta']) ?>"><span class="nav-icon"><?= e($modulo['icono']) ?></span><?= e($modulo['nombre']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="nav-label" style="grid-column:1/-1">Reportes</div>
            <a href="consultas/index.php"><span class="nav-icon">↗</span>Consultas</a>
        </nav>
        <div class="sidebar-bottom"><div><span class="online-dot"></span>Sistema en línea</div><div>Base de datos: Oracle 19c</div></div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="welcome"><h1>¡BIENVENIDO!</h1><p>Gestiona tu negocio de forma fácil y eficiente.</p></div>
            <label class="search"><span>⌕</span><input type="search" id="buscador" placeholder="Buscar en el sistema..." aria-label="Buscar módulos"></label>
        </header>

        <section class="stats" aria-label="Resumen general">
            <article class="stat lila"><div class="stat-icon">▣</div><div class="stat-label">Productos</div><div class="stat-number">—</div><div class="stat-foot">Conteo disponible al conectar datos</div></article>
            <article class="stat verde"><div class="stat-icon">▤</div><div class="stat-label">Inventario</div><div class="stat-number">—</div><div class="stat-foot">Existencias registradas</div></article>
            <article class="stat rosa"><div class="stat-icon">♟</div><div class="stat-label">Proveedores</div><div class="stat-number">—</div><div class="stat-foot">Proveedores registrados</div></article>
            <article class="stat azul"><div class="stat-icon">↗</div><div class="stat-label">Consultas</div><div class="stat-number">6</div><div class="stat-foot">Tipos de módulo disponibles</div></article>
        </section>

        <div class="section-head"><div><h2>Módulos del sistema</h2><p>Selecciona un módulo para comenzar</p></div></div>
        <section class="module-grid" id="modulos">
            <?php foreach ($modulos as $modulo): ?>
            <article class="module <?= e($modulo['tono']) ?>" data-search="<?= e(mb_strtolower($modulo['nombre'].' '.$modulo['descripcion'].' '.$modulo['categoria'], 'UTF-8')) ?>">
                <div class="module-icon"><?= e($modulo['icono']) ?></div>
                <h3><?= e($modulo['nombre']) ?></h3>
                <p><?= e($modulo['descripcion']) ?></p>
                <div class="module-bottom"><span class="module-category"><?= e($modulo['categoria']) ?></span><a class="open" href="<?= e($modulo['ruta']) ?>" aria-label="Abrir <?= e($modulo['nombre']) ?>">→</a></div>
            </article>
            <?php endforeach; ?>
        </section>
        <p class="empty" id="sinResultados">No se encontraron módulos con esa búsqueda.</p>
        <footer>Sistema de Punto de Venta · PHP y Oracle Database 19c<br>Proyecto académico</footer>
    </main>

    <aside class="right-panel">
        <div class="userbar"><span class="bell">♧</span><div class="avatar">A</div><div><div class="user-name">Administrador</div><div class="user-role">Sistema local</div></div></div>
        <section class="panel-card">
            <h2>Resumen del sistema</h2><p class="sub">Vista general de tu negocio</p>
            <div class="metric"><div class="ring"></div><div class="metric-info"><div class="metric-title">◇ Inventario</div><div class="metric-value">—</div><div class="metric-caption">Conecta la base de datos para ver el stock</div></div></div>
            <div class="metric"><div class="ring pink"></div><div class="metric-info"><div class="metric-title">△ Stock mínimo</div><div class="metric-value">—</div><div class="metric-caption">Productos que requieren revisión</div></div></div>
            <div class="metric"><div class="ring green"></div><div class="metric-info"><div class="metric-title">↗ Ventas del día</div><div class="metric-value">—</div><div class="metric-caption">Datos disponibles desde consultas</div></div></div>
            <div class="illustration" aria-hidden="true"><div class="bars"><span class="trend">↗</span><i></i><i></i><i></i><i></i></div></div>
            <div class="notice"><span>♧</span><div><b>¡Todo en orden!</b>El panel está listo para utilizarse. Las cifras reales deben conectarse a Oracle.</div></div>
        </section>
    </aside>
</div>
<script>
const buscador = document.getElementById('buscador');
const tarjetas = [...document.querySelectorAll('.module')];
const sinResultados = document.getElementById('sinResultados');
buscador.addEventListener('input', () => {
    const termino = buscador.value.trim().toLocaleLowerCase('es');
    let visibles = 0;
    tarjetas.forEach(tarjeta => {
        const coincide = tarjeta.dataset.search.toLocaleLowerCase('es').includes(termino);
        tarjeta.style.display = coincide ? 'flex' : 'none';
        if (coincide) visibles++;
    });
    sinResultados.style.display = visibles ? 'none' : 'block';
});
</script>
</body>
</html>
