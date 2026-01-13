// VALIDACIÓN DE SESIÓN ---------------------------------------------------------

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

// NAVEGACIÓN ------------------------------------------------------------------

function ir(pagina) {
  window.location.href = pagina;
}

function logout() {
  sessionStorage.clear();
  window.location.href = "index.html";
}

// CARGA DE COMPRAS ------------------------------------------------------------

document.addEventListener("DOMContentLoaded", cargarCompras);

function cargarCompras(inicio = null, fin = null) {
  let url = "/billar-app/backend/compras/listar_compras.php";

  if (inicio && fin) {
    url += `?inicio=${inicio}&fin=${fin}`;
  }

  fetch(url)
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;

      const tbody = document.getElementById("comprasBody");
      tbody.innerHTML = "";

      data.data.forEach(c => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${c.id_compra}</td>
          <td>${formatearFecha(c.fecha)}</td>
          <td>${c.proveedor}</td>
          <td>${formatearDinero(c.total)}</td>
          <td>${c.usuario}</td>
          <td class="estado-${c.estado}">${c.estado}</td>
          <td>
            ${
              c.estado === "activa"
                ? `<button class="btn-anular" onclick="verDetalleCompra(${c.id_compra})">Ver</button>`
                : `<button class="btn-anular" disabled>Anulada</button>`
            }
          </td>
        `;

        tbody.appendChild(tr);
      });
    });
}

// FILTROS ---------------------------------------------------------------------

function filtrarCompras() {
  const inicio = document.getElementById("fechaInicio").value;
  const fin = document.getElementById("fechaFin").value;

  if (!inicio || !fin) {
    alert("Selecciona ambas fechas");
    return;
  }

  cargarCompras(inicio, fin);
}

function limpiarFiltro() {
  document.getElementById("fechaInicio").value = "";
  document.getElementById("fechaFin").value = "";
  cargarCompras();
}

// DETALLE DE COMPRA ------------------------------------------------------------

let compraSeleccionada = null;

function verDetalleCompra(idCompra) {
  compraSeleccionada = idCompra;

  fetch(`/billar-app/backend/compras/detalle_compra.php?id_compra=${idCompra}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        alert("No se pudo cargar el detalle");
        return;
      }

      const tbody = document.getElementById("detalleCompraBody");
      if (!tbody) {
        console.error("No existe el tbody detalleCompraBody");
        return;
      }

      tbody.innerHTML = "";

      let total = 0;

      data.data.forEach(p => {
        total += Number(p.subtotal);

        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${p.nombre}</td>
          <td>${p.cantidad}</td>
          <td>${formatearDinero(p.costo_unitario)}</td>
          <td>${formatearDinero(p.subtotal)}</td>
        `;
        tbody.appendChild(tr);
      });

      // ⚠️ Total calculado solo para visualización
      document.getElementById("totalCompra").innerText =
      `Total: ${formatearDinero(total)}`;


      abrirModalDetalle();
    })
    .catch(err => {
      console.error(err);
      alert("Error de servidor al cargar el detalle");
    });
}


// MODAL -----------------------------------------------------------------------

function abrirModalDetalle() {
  document.getElementById("modalDetalleCompra").classList.add("active");
}

function cerrarModalDetalle() {
  document.getElementById("modalDetalleCompra").classList.remove("active");
  compraSeleccionada = null;
}

window.addEventListener("click", function (e) {
  const modal = document.getElementById("modalDetalleCompra");
  if (e.target === modal) cerrarModalDetalle();
});

// CONFIRMAR ANULACIÓN ----------------------------------------------------------

function confirmarAnulacionCompra() {
  if (!compraSeleccionada) return;

  if (!confirm("Esta acción revertirá el stock. ¿Deseas continuar?")) return;

  anularCompra(compraSeleccionada);
  cerrarModalDetalle();
}

// ANULAR COMPRA ----------------------------------------------------------------

function anularCompra(idCompra) {
  fetch("/billar-app/backend/compras/anular_compra.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id_compra: idCompra })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("Compra anulada correctamente");
        cargarCompras();
      } else {
        alert(data.message);
      }
    });
}

function formatearDinero(valor) {
  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "COP",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(Number(valor));
}

function formatearFecha(fecha) {
  if (!fecha) return "";
  const f = new Date(fecha);
  return f.toLocaleDateString("es-CO", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit"
  });
}
