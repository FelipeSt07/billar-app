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

// GESTIÓN DE COMPRAS ---------------------------------------------------------------------------------------------------------------------------------

let detalle = [];

document.addEventListener("DOMContentLoaded", () => {
  cargarProductos();
  document.getElementById("fecha_compra").valueAsDate = new Date();
});

function cargarProductos() {
  fetch("/billar-app/backend/productos/listar.php", {
    credentials: "include"
  })
    .then(r => r.json())
    .then(data => {
      const select = document.getElementById("productoSelect");
      data.data.forEach(p => {
        const opt = document.createElement("option");
        opt.value = p.id_producto;
        opt.textContent = p.nombre;
        select.appendChild(opt);
      });
    });
}

function agregarProductoCompra() {
  const id = productoSelect.value;
  const nombre = productoSelect.options[productoSelect.selectedIndex].text;
  const cantidad = Number(cantidadCompra.value);
  const costo = Number(costoUnitario.value);

  if (!id || cantidad <= 0 || costo <= 0) {
    alert("Datos inválidos");
    return;
  }

  const existente = detalle.find(d => d.id_producto == id);

  if (existente) {
    existente.costo_unitario = costo; // actualizar costo al último ingresado
    existente.cantidad += cantidad;
    existente.subtotal = existente.cantidad * existente.costo_unitario;
  } else {
    detalle.push({
      id_producto: id,
      nombre,
      cantidad,
      costo_unitario: costo,
      subtotal: cantidad * costo
    });
  }

  renderDetalle();

  cantidadCompra.value = "";
  costoUnitario.value = "";
  productoSelect.value = "";

}


function renderDetalle() {
  const formato = valor =>
    valor.toLocaleString("es-CO", {
      style: "currency",
      currency: "COP",
      minimumFractionDigits: 0
    });

  const tbody = document.getElementById("detalleCompra");
  const totalSpan = document.getElementById("totalCompra");

  tbody.innerHTML = "";

  detalle.forEach((d, i) => {
    tbody.innerHTML += `
      <tr>
        <td>${d.nombre}</td>
        <td>${d.cantidad}</td>
        <td>${formato(d.costo_unitario)}</td>
        <td>${formato(d.subtotal)}</td>
        <td>
          <button onclick="eliminarDetalle(${i})">❌</button>
        </td>
      </tr>
    `;
  });

  const total = calcularTotal();
  totalSpan.textContent = formato(total);
}


function eliminarDetalle(index) {
  detalle.splice(index, 1);
  renderDetalle();
}

function calcularTotal() {
  return detalle.reduce((acc, d) => acc + d.subtotal, 0);
}

// FINALIZAR COMPRA ---------------------------------------------------------------------------------------------------------------------------------

function guardarCompra() {

  if (detalle.length === 0) {
    alert("Debe agregar al menos un producto");
    return;
  }

  const proveedor = document.getElementById("proveedor").value.trim();
  const fecha = document.getElementById("fecha_compra").value;

  if (!fecha) {
    alert("Debe seleccionar una fecha");
    return;
  }

  const productos = detalle.map(d => ({
    id_producto: d.id_producto,
    cantidad: d.cantidad,
    costo_unitario: d.costo_unitario
  }));

  const total = calcularTotal();

  const payload = {
    proveedor,
    fecha,
    total,
    productos
  };

  fetch("/billar-app/backend/compras/guardar_compra.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        alert(data.message || "Error al guardar la compra");
        return;
      }

      alert("Compra registrada correctamente");

      // 🔄 Reset estado
      detalle = [];
      renderDetalle();

      document.getElementById("proveedor").value = "";
      document.getElementById("fecha_compra").valueAsDate = new Date();

    })
    .catch(err => {
      console.error(err);
      alert("Error de servidor");
    });
}


// MODAL PRODUCTOS (COMPRAS) ------------------------------------------------------------
const modal = document.getElementById("modalProducto");

function abrirModalProducto() {
  document.getElementById("modalProducto").classList.add("active");

  document.getElementById("tituloModal").textContent = "Agregar producto";
  document.getElementById("id_producto").value = "";
  document.getElementById("nombre").value = "";
  document.getElementById("precio").value = "";
  document.getElementById("categoria").value = "";
}

function cerrarModal() {
  document.getElementById("modalProducto").classList.remove("active");
}

// GUARDAR PRODUCTO (SIN STOCK, SIN EDICIÓN) --------------------------------------------

function guardarProducto() {
  const nombre = document.getElementById("nombre").value.trim();
  const precio = document.getElementById("precio").value;
  const categoria = document.getElementById("categoria").value;

  if (!nombre) {
    alert("El nombre del producto es obligatorio");
    return;
  }

  if (!categoria) {
    alert("Debe seleccionar una categoría");
    return;
  }

  fetch("/billar-app/backend/productos/guardar.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({
      nombre,
      precio: precio || 0,
      categoria
    })
  })
  .then(res => res.json())
  .then(data => {
    if (!data.success) {
      alert(data.message || "Error al guardar producto");
      return;
    }

    cerrarModal();

    cargarProductos().then(() => {
      if (data.id_producto) {
        setTimeout(() => {
          document.getElementById("productoSelect").value = data.id_producto;
        }, 300);
      }
    });

  })
  .catch(err => {
    console.error(err);
    alert("Error de servidor");
  });
}

// CERRAR MODAL AL HACER CLICK FUERA ---------------------------------------------------------------------------------------------------------------

window.addEventListener("click", function (e) {
  if (e.target === modal) {
    cerrarModal();
  }
});
