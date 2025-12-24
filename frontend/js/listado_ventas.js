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

//CARGA DE VENTAS ---------------------------------------------------------------------------------------------------------------------------------
document.addEventListener("DOMContentLoaded", cargarVentas);

function cargarVentas() {
  fetch("/billar-app/backend/ventas/listar_ventas.php")
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;

      const tbody = document.getElementById("ventasBody");
      tbody.innerHTML = "";

      data.data.forEach(v => {
        const tr = document.createElement("tr");

        tr.innerHTML = `
          <td>${v.id_venta}</td>
          <td>${v.fecha_venta}</td>
          <td>${v.usuario}</td>
          <td>$${Number(v.total).toFixed(2)}</td>
          <td class="estado-${v.estado}">
            ${v.estado}
          </td>
          <td>
            ${
              v.estado === "activa"
              ? `<button class="btn-anular" onclick="verDetalleVenta(${v.id_venta})">Ver </button>`
              : `<button class="btn-anular" disabled>Anulada</button>`
            }
          </td>
        `;
        tbody.appendChild(tr);
      });
    });
}

// DETALLE DE VENTA ---------------------------------------------------------------------------------------------------------------------------------
let ventaSeleccionada = null;

function verDetalleVenta(idVenta) {
  ventaSeleccionada = idVenta;

  fetch(`/billar-app/backend/ventas/detalle_venta.php?id_venta=${idVenta}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        alert("No se pudo cargar el detalle");
        return;
      }

      const tbody = document.getElementById("detalleVentaBody");
      tbody.innerHTML = "";

      data.data.forEach(p => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${p.nombre}</td>
          <td>${p.cantidad}</td>
          <td>$${Number(p.precio_unitario).toFixed(2)}</td>
          <td>$${Number(p.subtotal).toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
      });

      abrirModalDetalle();
    });
}

// FUNCIONES MODAL ---------------------------------------------------------------------------------------------------------------------------------  

function abrirModalDetalle() {
  document.getElementById("modalDetalle").classList.add("active");
  document.getElementById("tituloDetalle").innerText =
  `Detalle de la venta #${ventaSeleccionada}`;
}

function cerrarModalDetalle() {
  document.getElementById("modalDetalle").classList.remove("active");
  ventaSeleccionada = null;
}

window.addEventListener("click", function (e) {
  const modalDetalle = document.getElementById("modalDetalle");

  if (e.target === modalDetalle) {
    cerrarModalDetalle();
  }
});




// CONFIRMAR ANULACIÓN ---------------------------------------------------------------------------------------------------------------------------------

function confirmarAnulacion() {
  if (!ventaSeleccionada) return;

  if (!confirm("Esta acción devolverá el stock. ¿Continuar?")) return;

  anularVenta(ventaSeleccionada);
  cerrarModalDetalle();
}

// ANULAR VENTA -------------------------------------------------------------------------------------------------------------------------------------

function anularVenta(idVenta) {

  fetch("/billar-app/backend/ventas/anular_ventas.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id_venta: idVenta })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("Venta anulada");
      cargarVentas();
    } else {
      alert(data.message);
    }
  });
}
