// VALIDACIÓN DE SESIÓN

const usuario = sessionStorage.getItem("usuario");
const rol = sessionStorage.getItem("rol");

if (!usuario || !rol) {
  window.location.href = "index.html";
}

if (rol !== "admin") {
  const btnUsuarios = document.getElementById("btnUsuarios");
  const btnProductos = document.getElementById("btnProductos");

  if (btnUsuarios) btnUsuarios.style.display = "none";
  if (btnProductos) btnProductos.style.display = "none";
}


// NAVEGACIÓN ---------------------------------------------------------------------------------------------------------------------------------------

function ir(pagina) {
  window.location.href = pagina;
}

function logout() {
  sessionStorage.clear();
  window.location.href = "index.html";
}

// REPORTE MENSUAL 

function cargarReporte() {
  const mesSeleccionado = document.getElementById("mesSeleccionado").value;

  if (!mesSeleccionado) {
    alert("Selecciona un mes");
    return;
  }

  const [anio, mes] = mesSeleccionado.split("-");

  fetch(`/billar-app/backend/reportes/reporte_mensual.php?anio=${anio}&mes=${mes}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        alert(data.message || "Error al cargar el reporte");
        return;
      }

      pintarCards(data.resumen);
      pintarTablaDiaria(data.diario);
    })
    .catch(err => {
      console.error(err);
      alert("Error de conexión con el servidor");
    });
}

// PINTAR CARDS ---------------------------------------------------------------------------------------------------------------------------------

function pintarCards(resumen) {
  document.getElementById("cardIngresos").innerText =
    formatearDinero(resumen.ingresos);

  document.getElementById("cardGastos").innerText =
    formatearDinero(resumen.gastos);

  const utilidad = Number(resumen.utilidad);
  const utilidadEl = document.getElementById("cardUtilidad");

  utilidadEl.innerText = formatearDinero(utilidad);
  utilidadEl.className = utilidad >= 0 ? "positiva" : "negativa";
}


// TABLA DIARIA --------------------------------------------------------------------------------------------------------------------------------------

function pintarTablaDiaria(diario) {
  const tbody = document.getElementById("tablaReporteBody");
  tbody.innerHTML = "";

  if (!diario || diario.length === 0) {
    const tr = document.createElement("tr");
    tr.innerHTML = `<td colspan="3">No hay movimientos en este mes</td>`;
    tbody.appendChild(tr);
    return;
  }

  diario.forEach(d => {
    const tr = document.createElement("tr");

    tr.innerHTML = `
      <td>${formatearFecha(d.dia)}</td>
      <td>${formatearDinero(d.ingresos)}</td>
      <td class="${Number(d.utilidad) >= 0 ? 'positiva' : 'negativa'}">
        ${formatearDinero(d.utilidad)}
      </td>
    `;

    tbody.appendChild(tr);
  });
}



// UTILIDADES ---------------------------------------------------------------------------------------------------------------------------------------


function formatearFecha(fecha) {
  // fecha viene como YYYY-MM-DD
  const [anio, mes, dia] = fecha.split("-");
  return `${dia}/${mes}/${anio}`;
}

// Formatea un número como moneda colombiana

function formatearDinero(valor) {
  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "COP",
    minimumFractionDigits: 0
  }).format(valor);
}

