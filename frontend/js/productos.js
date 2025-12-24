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


// MODAL PRODUCTOS  ---------------------------------------------------------------------------------------------------------------------------------

const modal = document.getElementById("modalProducto");
const tituloModal = document.getElementById("tituloModal");

let editando = false;

function abrirModal(producto = null) {
  document.getElementById("modalProducto").classList.add("active");

  if (producto) {
    editando = true;
    document.getElementById("tituloModal").textContent = "Editar producto";
    document.getElementById("id_producto").value = producto.id_producto;
    document.getElementById("nombre").value = producto.nombre;
    document.getElementById("precio").value = producto.precio;
    document.getElementById("stock").value = producto.stock;
  } else {
    editando = false;
    document.getElementById("tituloModal").textContent = "Agregar producto";
    document.getElementById("id_producto").value = "";
    document.getElementById("nombre").value = "";
    document.getElementById("precio").value = "";
    document.getElementById("stock").value = "";
  }
}

function cerrarModal() {
  document.getElementById("modalProducto").classList.remove("active");
}

// EDITAR y GUARDAR PRODUCTO ------------------------------------------------------------------------------------------------------------------------------------

function guardarProducto() {
  console.log("🔥 guardarProducto ejecutado");

  const id_producto = document.getElementById("id_producto").value;
  const nombre = document.getElementById("nombre").value.trim();
  const precio = document.getElementById("precio").value;
  const stock = document.getElementById("stock").value;

  if (!nombre || !precio || !stock) {
    alert("Complete todos los campos");
    return;
  }

  fetch("/billar-app/backend/productos/guardar.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ id_producto, nombre, precio, stock })
  })
  .then(r => r.json())
  .then(data => {
    console.log("Respuesta backend:", data);
    if (!data.success) {
      alert(data.message);
      return;
    }
    cerrarModal();
    cargarProductos();
  })
  .catch(err => {
    console.error(err);
    alert("Error de servidor");
  });
}

// ELIMINAR PRODUCTO ------------------------------------------------------------------------------------------------------------------------------------

function eliminar(id) {
  if (!confirm("¿Eliminar este producto?")) return;

  fetch("/billar-app/backend/productos/eliminar.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ id_producto: id })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) {
      alert(data.message);
      return;
    }
    cargarProductos();
  })
  .catch(() => alert("Error de servidor"));
}

// CERRAR MODAL AL HACER CLICK FUERA ---------------------------------------------------------------------------------------------------------------

window.addEventListener("click", function (e) {
  if (e.target === modal) {
    cerrarModal();
  }
});

// CARGAR PRODUCTOS ----------------------------------------------------------------------------------------------------------------------------------

document.addEventListener("DOMContentLoaded", cargarProductos);

function cargarProductos() {
  fetch("/billar-app/backend/productos/listar.php",{
    credentials: "include"
})
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        alert("Error al cargar productos");
        return;
      }

      renderProductos(data.data);
    })
    .catch(err => {
      console.error(err);
      alert("Error de conexión con el servidor");
    });
}

// RENDERIZAR PRODUCTOS EN LA TABLA ------------------------------------------------------------------------------------------------------------------

function renderProductos(productos) {
  const tbody = document.getElementById("tablaProductos");
  tbody.innerHTML = "";

  productos.forEach(p => {
    const tr = document.createElement("tr");

    tr.innerHTML = `
      <td>${p.nombre}</td>
      <td>$${p.precio}</td>
      <td>${p.stock}</td>
      <td class="acciones">
        <button class="btn-edit">✏️</button>
        <button class="btn-delete">🗑️</button>
      </td>
    `;

    tr.querySelector(".btn-edit").onclick = () => abrirModal(p);
    tr.querySelector(".btn-delete").onclick = () => eliminar(p.id_producto);

    tbody.appendChild(tr);
  });
}






