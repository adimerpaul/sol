// src/utils/ImprimirTicket.js
import { Printd } from 'printd'

export class Imprimir {
  static ticket (venta) {
    if (!venta) return

    const date = venta.date || ''
    const time = (venta.time || '').substring(0, 8)
    const mesa = venta.mesa || 'MESA'
    const pago = venta.pago || 'EFECTIVO'
    const numero = venta.numero || ''
    const llamada = venta.llamada || ''
    const userName = venta.user?.name || ''
    const comment = venta.comment || ''
    const type = venta.type || 'INGRESO'

    const detalles = venta.detalles || venta.details || []

    let filas = ''
    let totalCalc = 0
    detalles.forEach(d => {
      const qty = Number(d.qty || d.quantity || 0)
      const price = Number(d.price || 0)
      const subtotal = Number(d.subtotal || qty * price)
      totalCalc += subtotal

      filas += `
        <tr>
          <td class="col-cant">${qty}</td>
          <td class="col-detalle">${(d.name || d.product || '').toUpperCase()}</td>
          <td class="col-pu">${price.toFixed(0)}</td>
          <td class="col-total">${subtotal.toFixed(0)}</td>
        </tr>`
    })

    const total = Number(venta.total || totalCalc || 0)
    const logoSrc = `${window.location.origin}/chicken-logo.png`

    const html = `
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; }
        .ticket-wrapper {
          width: 7.2cm;
          padding: 4px 6px;
          font-size: 11px;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .mt-4 { margin-top: 4px; }
        .mt-8 { margin-top: 8px; }
        .mb-4 { margin-bottom: 4px; }
        .logo img {
          max-width: 80px;
          display: block;
          margin: 0 auto 2px auto;
        }
        .nombre-local {
          font-size: 14px;
          font-weight: bold;
        }
        .contacto {
          font-size: 11px;
        }
        .direccion {
          font-size: 10px;
        }
        .fecha-hora {
          font-size: 11px;
          margin-top: 4px;
          margin-bottom: 4px;
          display: flex;
          justify-content: space-between;
        }
        hr {
          border: none;
          border-top: 1px dashed #000;
          margin: 4px 0;
        }
        table.items {
          width: 100%;
          border-collapse: collapse;
          margin-top: 4px;
        }
        table.items th,
        table.items td {
          border: 1px solid #000;
          padding: 2px 3px;
        }
        table.items th {
          font-size: 10px;
          text-align: center;
        }
        .col-cant { width: 16%; text-align: center; }
        .col-detalle { width: 44%; text-align: left; }
        .col-pu { width: 15%; text-align: right; }
        .col-total { width: 25%; text-align: right; }
        .total-section {
          margin-top: 6px;
          font-size: 12px;
        }
        .total-row {
          display: flex;
          justify-content: flex-end;
          margin-top: 2px;
        }
        .total-row span:first-child {
          margin-right: 4px;
          font-weight: bold;
        }
        .ticket-line {
          margin-top: 10px;
          text-align: center;
          font-size: 15px;
          font-weight: bold;
        }
        .ticket-line span.mesa {
          font-size: 18px;
          font-style: italic;
        }
        .box-firma {
          margin-top: 6px;
          width: 100%;
          height: 70px;
          border: 1px solid #000;
        }
        .pie {
          margin-top: 4px;
          text-align: center;
          font-size: 9px;
        }
        .usuario {
          margin-top: 2px;
          text-align: left;
          font-size: 9px;
          font-weight: bold;
        }
        .llamada-num {
          position: absolute;
          right: 6px;
          top: 4px;
          font-size: 22px;
          font-weight: bold;
        }
        .cliente-nombre {
          text-align: center;
          font-size: 14px;
          font-weight: bold;
          margin-top: 2px;
        }
      </style>

      <div class="ticket-wrapper">
        <div style="position:relative;">
          ${type === 'INGRESO' && llamada
      ? `<div class="llamada-num">${llamada}</div>`
      : ''
    }
          <div class="logo">
            <img src="${logoSrc}" alt="Chicken's Garden">
          </div>
          <div class="center nombre-local">CHICKEN'S GARDEN</div>
          <div class="center contacto">CONTACTOS: 77909517</div>
          <div class="center direccion">Mercado Campero - Calle 6 N° 21</div>
          ${type === 'INGRESO' && venta.name && venta.name !== 'SN'
      ? `<div class="cliente-nombre">${venta.name}</div>`
      : ''
    }
          <div class="fecha-hora">
            <span>${date}</span>
            <span>${time}</span>
          </div>
          <hr>
          <table class="items">
            <thead>
              <tr>
                <th>CANT</th>
                <th>DETALLE</th>
                <th>P/U</th>
                <th>TOTAL</th>
              </tr>
            </thead>
            <tbody>
              ${filas || '<tr><td colspan="4" style="text-align:center;">SIN DETALLE</td></tr>'}
            </tbody>
          </table>
          <div class="total-section">
            <div class="total-row">
              <span>TOTAL:</span>
              <span>${total.toFixed(2)}</span>
            </div>
            <div class="total-row">
              <span>Pago:</span>
              <span>${pago}</span>
            </div>
          </div>
          <div class="ticket-line">
            TICKET ${numero} <span class="mesa">${mesa}</span>
          </div>
          <div class="box-firma">
          ${comment
      ? `<div style="margin-top:4px;">${comment}</div>`
      : ''
    }
</div>
          ${type === 'INGRESO'
      ? `<div class="pie">GRACIAS POR SU COMPRA, BUEN PROVECHO</div>`
      : ''
    }
          <div class="usuario">
            Usuario: ${userName}
          </div>
        </div>
      </div>
    `
    const area = Imprimir._getArea()
    area.innerHTML = html
    const d = new Printd()
    d.print(area)
  }

  static _getArea () {
    let el = document.getElementById('myelement')
    if (!el) {
      el = document.createElement('div')
      el.id = 'myelement'
      el.style.position = 'fixed'
      el.style.left = '-10000px'
      el.style.top = '-10000px'
      document.body.appendChild(el)
    }
    return el
  }

  static cierreCaja (cierre) {
    if (!cierre) return

    const date     = cierre.date || ''
    const userName = cierre.user?.name || ''

    const totalIngresos     = Number(cierre.total_ingresos || 0)
    const totalEgresos      = Number(cierre.total_egresos || 0)
    const totalCajaIni      = Number(cierre.total_caja_inicial || 0)
    const tickets           = Number(cierre.tickets || 0)
    const montoSistema      = Number(cierre.monto_sistema || 0)      // SISTEMA (solo EFECTIVO)
    const montoEfectivo     = Number(cierre.monto_efectivo || 0)     // contado EFECTIVO
    const montoQr           = Number(cierre.monto_qr || 0)           // contado QR
    const diferencia        = Number(cierre.diferencia || 0)         // diferencia TOTAL
    const obs               = cierre.observacion || ''

    // Desglose por método de pago (pueden venir undefined si es cierre antiguo)
    const ingresosEfectivo  = Number(cierre.ingresos_efectivo ?? totalIngresos)
    const ingresosQr        = Number(cierre.ingresos_qr || 0)
    const ingresosTarjeta   = Number(cierre.ingresos_tarjeta || 0)
    const ingresosOnline    = Number(cierre.ingresos_online || 0)

    // Totales esperados / contados
    const esperadoTotal = Number(cierre.esperado_total ?? (montoSistema + ingresosQr))
    const contadoTotal  = Number(cierre.contado_total ?? (montoEfectivo + montoQr))

    // Diferencias por tipo y total
    const diferenciaEf  = montoEfectivo - montoSistema
    const diferenciaQr  = montoQr - ingresosQr
    const diferenciaTot = diferencia || (contadoTotal - esperadoTotal)

    const logoSrc = `${window.location.origin}/chicken-logo.png`

    const html = `
    <style>
      * { box-sizing: border-box; margin: 0; padding: 0; }
      body { font-family: Arial, sans-serif; }
      .ticket-wrapper {
        width: 7.2cm;
        padding: 4px 6px;
        font-size: 11px;
      }
      .center { text-align: center; }
      .bold { font-weight: bold; }
      .logo img {
        max-width: 80px;
        display: block;
        margin: 0 auto 2px auto;
      }
      hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }
      .titulo {
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        margin-top: 4px;
      }
      .resumen-row {
        display: flex;
        justify-content: space-between;
        margin-top: 2px;
      }
      .resumen-row span:first-child {
        font-weight: bold;
      }
      .pie {
        margin-top: 6px;
        font-size: 9px;
        text-align: center;
      }
      .usuario {
        margin-top: 2px;
        font-size: 9px;
        font-weight: bold;
      }
    </style>

    <div class="ticket-wrapper">
      <div class="logo">
        <img src="${logoSrc}" alt="Chicken's Garden">
      </div>
      <div class="titulo">CIERRE DE CAJA</div>
      <div class="center">Fecha: ${date}</div>
      <div class="center">Usuario cierre: ${userName}</div>
      <hr>

      <!-- RESUMEN POR MÉTODO DE PAGO (SISTEMA) -->
      <div class="resumen-row"><span>Ing. EFECTIVO:</span><span>${ingresosEfectivo.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Ing. QR:</span><span>${ingresosQr.toFixed(2)} Bs</span></div>
      <!--div class="resumen-row"><span>Ing. TARJETA:</span><span>${ingresosTarjeta.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Ing. ONLINE:</span><span>${ingresosOnline.toFixed(2)} Bs</span></div-->
      <hr>

      <!-- RESUMEN SISTEMA SOLO EFECTIVO -->
      <!--div class="resumen-row"><span>Caja inicial:</span><span>${totalCajaIni.toFixed(2)} Bs</span></div-->
      <div class="resumen-row"><span>Ingresos caja (efectivo):</span><span>${ingresosEfectivo.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Egresos:</span><span>${totalEgresos.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Tickets:</span><span>${tickets}</span></div>
      <hr>
      <div class="resumen-row"><span>Sistema (efectivo):</span><span>${montoSistema.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Efectivo contado:</span><span>${montoEfectivo.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Dif. efectivo:</span><span>${diferenciaEf.toFixed(2)} Bs</span></div>
      <hr>

      <!-- QR -->
      <div class="resumen-row"><span>QR esperado:</span><span>${ingresosQr.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>QR contado:</span><span>${montoQr.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Dif. QR:</span><span>${diferenciaQr.toFixed(2)} Bs</span></div>
      <hr>

      <!-- TOTALES -->
      <div class="resumen-row"><span>Total esperado:</span><span>${esperadoTotal.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Total contado:</span><span>${contadoTotal.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Diferencia total:</span><span>${diferenciaTot.toFixed(2)} Bs</span></div>

      ${obs ? `<div class="pie" style="margin-top:4px;">Obs: ${obs}</div>` : ''}
      <hr>
      <div class="pie">Gracias por su trabajo</div>
      <div class="usuario">Firmado: ____________________</div>
    </div>
  `
    const area = Imprimir._getArea()
    area.innerHTML = html
    const d = new Printd()
    d.print(area)
  }


  static reporteUsuarios (data) {
    const usuarios = data?.usuarios || []
    const dateFrom = data?.date_from || ''
    const dateTo = data?.date_to || ''
    const logoSrc = `${window.location.origin}/chicken-logo.png`

    let filas = ''
    let totalNeto = 0

    usuarios.forEach(u => {
      const neto = Number(u.neto || 0)
      totalNeto += neto
      filas += `
      <tr>
        <td>${u.user_name}</td>
        <td class="num">${Number(u.total_ingresos || 0).toFixed(2)}</td>
        <td class="num">${Number(u.total_egresos || 0).toFixed(2)}</td>
        <!--td class="num">${Number(u.total_caja || 0).toFixed(2)}</td!-->
        <td class="num">${neto.toFixed(2)}</td>
        <td class="num">${Number(u.tickets || 0)}</td>
      </tr>
    `
    })

    const html = `
    <style>
      * { box-sizing: border-box; margin: 0; padding: 0; }
      body { font-family: Arial, sans-serif; }
      .ticket-wrapper {
        width: 8cm;
        padding: 4px 6px;
        font-size: 10px;
      }
      .center { text-align: center; }
      .logo img { max-width: 70px; display:block; margin:0 auto 2px auto; }
      hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }
      table { width: 100%; border-collapse: collapse; margin-top: 4px; }
      th, td {
        border: 1px solid #000;
        padding: 2px 3px;
      }
      th { font-size: 9px; }
      .num { text-align: right; }
      .pie { margin-top: 4px; text-align:center; font-size:9px; }
    </style>

    <div class="ticket-wrapper">
      <div class="logo">
        <img src="${logoSrc}" alt="Chicken's Garden">
      </div>
      <div class="center" style="font-weight:bold;">RESUMEN DE VENTAS POR USUARIO</div>
      <div class="center">Desde: ${dateFrom || '-'} Hasta: ${dateTo || '-'}</div>
      <hr>
      <table>
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Ingreso</th>
            <th>Egreso</th>
            <!--th>Caja</th!-->
            <th>Neto</th>
            <th>Tickets</th>
          </tr>
        </thead>
        <tbody>
          ${filas || '<tr><td colspan="6" class="center">Sin datos</td></tr>'}
        </tbody>
      </table>
      <div class="pie">Total neto: ${totalNeto.toFixed(2)} Bs</div>
    </div>
  `
    const area = Imprimir._getArea()
    area.innerHTML = html
    const d = new Printd()
    d.print(area)
  }

  static reporteProductosPorUsuario (data) {
    const productos = data?.productos || []
    const logoSrc = `${window.location.origin}/chicken-logo.png`

    let htmlBody = ''

    productos.forEach(bloque => {
      const userName = bloque.user_name
      let filas = ''

      bloque.items.forEach(it => {
        filas += `
        <tr>
          <td>${it.name}</td>
          <td class="num">${Number(it.qty || 0)}</td>
          <td class="num">${Number(it.subtotal || 0).toFixed(2)}</td>
        </tr>
      `
      })

      htmlBody += `
      <div class="user-block">
        <div class="user-title">Usuario: ${userName}</div>
        <table>
          <thead>
            <tr>
              <th>Producto</th>
              <th>Cant.</th>
              <th>Total Bs</th>
            </tr>
          </thead>
          <tbody>
            ${filas || '<tr><td colspan="3">Sin productos</td></tr>'}
          </tbody>
        </table>
      </div>
      <hr>
    `
    })

    const html = `
    <style>
      * { box-sizing: border-box; margin: 0; padding: 0; }
      body { font-family: Arial, sans-serif; }
      .ticket-wrapper {
        width: 8cm;
        padding: 4px 6px;
        font-size: 10px;
      }
      .center { text-align: center; }
      .logo img { max-width: 70px; display:block; margin:0 auto 2px auto; }
      hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }
      table { width: 100%; border-collapse: collapse; margin-top: 3px; }
      th, td {
        border: 1px solid #000;
        padding: 2px 3px;
      }
      th { font-size: 9px; }
      .num { text-align: right; }
      .user-title { font-weight:bold; margin-top:4px; }
    </style>

    <div class="ticket-wrapper">
      <div class="logo">
        <img src="${logoSrc}" alt="Chicken's Garden">
      </div>
      <div class="center" style="font-weight:bold;">PRODUCTOS POR USUARIO</div>
      <hr>
      ${htmlBody || '<div class="center">Sin datos</div>'}
    </div>
  `
    const area = Imprimir._getArea()
    area.innerHTML = html
    const d = new Printd()
    d.print(area)
  }

  // NUEVO: VENTAS DETALLADAS POR USUARIO (usa data.ventas de resumenPorUsuario)
  static reporteVentasPorUsuario (data) {
    const ventas = data?.ventas || []
    const usuario = (data?.usuarios && data.usuarios[0]) || null
    const logoSrc = `${window.location.origin}/chicken-logo.png`
    const dateFrom = data?.date_from || ''
    const dateTo = data?.date_to || ''

    let filas = ''
    let total = 0

    ventas.forEach(v => {
      const t = Number(v.total || 0)
      total += t
      filas += `
        <tr>
          <td>${v.numero}</td>
          <td>${v.date}</td>
          <td>${String(v.time || '').substring(0, 8)}</td>
          <td>${v.mesa}</td>
          <td>${v.pago}</td>
          <td class="num">${t.toFixed(2)}</td>
        </tr>
      `
    })

    const html = `
    <style>
      * { box-sizing: border-box; margin: 0; padding: 0; }
      body { font-family: Arial, sans-serif; }
      .ticket-wrapper {
        width: 8cm;
        padding: 4px 6px;
        font-size: 10px;
      }
      .center { text-align: center; }
      .logo img { max-width: 70px; display:block; margin:0 auto 2px auto; }
      hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }
      table { width: 100%; border-collapse: collapse; margin-top: 3px; }
      th, td {
        border: 1px solid #000;
        padding: 2px 3px;
      }
      th { font-size: 9px; }
      .num { text-align: right; }
      .titulo { font-weight:bold; margin-top:2px; }
    </style>

    <div class="ticket-wrapper">
      <div class="logo">
        <img src="${logoSrc}" alt="Chicken's Garden">
      </div>
      <div class="center titulo">VENTAS POR USUARIO</div>
      <div class="center">
        Usuario: ${usuario ? usuario.user_name : ''}
      </div>
      <div class="center">
        Desde: ${dateFrom || '-'} Hasta: ${dateTo || '-'}
      </div>
      <hr>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Mesa</th>
            <th>Pago</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          ${filas || '<tr><td colspan="6" class="center">Sin ventas</td></tr>'}
        </tbody>
      </table>
      <div class="center" style="margin-top:4px;">
        Total ventas: ${total.toFixed(2)} Bs
      </div>
    </div>
  `
    const area = Imprimir._getArea()
    area.innerHTML = html
    const d = new Printd()
    d.print(area)
  }
  static reporteUltimoCierreUsuarios (data) {
    const logoSrc = `${window.location.origin}/chicken-logo.png`
    const date = data.date || ''
    const usuarios = data.usuarios || []

    let filas = ''
    usuarios.forEach(u => {
      filas += `
      <tr>
        <td>${u.user_name}</td>
        <td class="num">${u.efectivo.toFixed(2)}</td>
        <td class="num">${u.qr.toFixed(2)}</td>
        <td class="num">${u.total.toFixed(2)}</td>
        <td class="num">${u.tickets}</td>
      </tr>
    `
    })

    const html = `
  <style>
    body { font-family: Arial, sans-serif; font-size: 10px; }
    .ticket { width: 8cm; }
    table { width:100%; border-collapse: collapse; }
    th, td { border:1px solid #000; padding:3px; }
    th { background:#eee; }
    .num { text-align:right; }
    .center { text-align:center; }
  </style>

  <div class="ticket">
    <div class="center">
      <img src="${logoSrc}" width="70"><br>
      <b>REPORTE ÚLTIMO CIERRE DE CAJA</b><br>
      Fecha: ${date}
    </div>
    <br>
    <table>
      <thead>
        <tr>
          <th>Usuario</th>
          <th>Efectivo</th>
          <th>QR</th>
          <th>Total</th>
          <th>Tickets</th>
        </tr>
      </thead>
      <tbody>
        ${filas || '<tr><td colspan="5" class="center">Sin datos</td></tr>'}
      </tbody>
    </table>

    <div class="center" style="margin-top:5px;">
      TOTAL GENERAL: ${Number(data.total || 0).toFixed(2)} Bs
    </div>
  </div>
  `

    const area = Imprimir._getArea()
    area.innerHTML = html
    new Printd().print(area)
  }

}
