let carrito = [];
let productosGlobal = [];


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

// CARGA DE PRODUCTOS --------------------------------------------------------------------------------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
  cargarProductos();
});

function cargarProductos() {
  fetch("/billar-app/backend/productos/obtener_productos.php", {
    credentials: "include"
  })
    .then(res => res.json())
    .then(resp => {

      if (!resp.success) {
        alert("No se pudieron cargar los productos");
        return;
      }

      productosGlobal = resp.data; // 👈 GUARDAMOS EN MEMORIA
      renderProductos(productosGlobal);
    })
    .catch(err => {
      console.error("Error cargando productos:", err);
      alert("Error de conexión con el servidor");
    });
}
// RENDERIZAR PRODUCTOS --------------------------------------------------------------------------------------------------------------------------------

function renderProductos(productos) {
  const contenedor = document.getElementById("listaProductos");
  contenedor.innerHTML = "";

  if (productos.length === 0) {
    contenedor.innerHTML = "<p>No hay productos</p>";
    return;
  }

  productos.forEach(prod => {
    const card = document.createElement("div");
    card.classList.add("producto-card");

    card.innerHTML = `
      <h4>${prod.nombre}</h4>
      <small>Stock: ${prod.stock}</small>
      <p>$${Number(prod.precio).toLocaleString()}</p>
      <button class="btn-primary"
        ${prod.stock <= 0 ? "disabled" : ""}
        onclick="agregarAlCarrito(
          ${prod.id_producto},
          '${prod.nombre.replace(/'/g, "\\'")}',
          ${prod.precio},
          ${prod.stock}
        )">
        Agregar
      </button>
    `;

    contenedor.appendChild(card);
  });
}
// FILTRAR PRODUCTOS --------------------------------------------------------------------------------------------------------------------------------

function filtrarProductos() {
  const texto = document
    .getElementById("buscadorProductos")
    .value
    .toLowerCase();

  const filtrados = productosGlobal.filter(p =>
    p.nombre.toLowerCase().includes(texto)
  );

  renderProductos(filtrados);
}


// AGREGAR AL CARRITO DE COMPRAS --------------------------------------------------------------------------------------------------------------------------------

function agregarAlCarrito(id, nombre, precio, stock) {

  const item = carrito.find(p => p.id === id);

  if (item) {
    if (item.cantidad >= stock) {
      alert("Stock insuficiente");
      return;
    }
    item.cantidad++;
  } else {
    carrito.push({
      id,
      nombre,
      precio,
      cantidad: 1,
      stock
    });
  }

  renderCarrito();
}

// RENDERIZAR CARRITO DE COMPRAS --------------------------------------------------------------------------------------------------------------------------------

function renderCarrito() {
  const tbody = document.getElementById("carritoBody");
  const totalSpan = document.getElementById("totalVenta");

  tbody.innerHTML = "";
  let total = 0;

  carrito.forEach(item => {
    const subtotal = item.precio * item.cantidad;
    total += subtotal;

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${item.nombre}</td>
      <td>
        <input type="number"
          min="1"
          max="${item.stock}"
          value="${item.cantidad}"
          onchange="cambiarCantidad(${item.id}, this.value)">
      </td>
      <td>$${item.precio.toLocaleString()}</td>
      <td>$${subtotal.toLocaleString()}</td>
      <td>
        <button onclick="eliminarDelCarrito(${item.id})">❌</button>
      </td>
    `;

    tbody.appendChild(tr);
  });

  totalSpan.textContent = total.toLocaleString();
}

//CAMBIAR CANTIDAD EN EL CARRITO --------------------------------------------------------------------------------------------------------------------------------

function cambiarCantidad(id, cantidad) {
  cantidad = parseInt(cantidad);

  const item = carrito.find(p => p.id === id);
  if (!item) return;

  if (cantidad > item.stock) {
    alert("Cantidad supera el stock");
    cantidad = item.stock;
  }

  if (cantidad <= 0) {
    eliminarDelCarrito(id);
  } else {
    item.cantidad = cantidad;
  }

  renderCarrito();
}

// ELIMINAR DEL CARRITO --------------------------------------------------------------------------------------------------------------------------------

function eliminarDelCarrito(id) {
  carrito = carrito.filter(p => p.id !== id);
  renderCarrito();
}

// CONFIRMAR VENTA --------------------------------------------------------------------------------------------------------------------------------

function confirmarVenta() {

  if (carrito.length === 0) {
    alert("El carrito está vacío");
    return;
  }

  fetch("/billar-app/backend/ventas/guardar_directa.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      productos: carrito.map(p => ({
        id_producto: p.id,
        cantidad: p.cantidad
      }))
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert("Venta registrada correctamente");
      carrito = [];
      renderCarrito();
      cargarProductos(); // refrescar stock
    } else {
      alert(data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert("Error al registrar la venta (ver consola)");
  });
}






