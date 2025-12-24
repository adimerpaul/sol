var u={},T;function S(){if(T)return u;T=1,Object.defineProperty(u,"__esModule",{value:!0}),u.Printd=u.createIFrame=u.createLinkStyle=u.createStyle=void 0;var _=/^(((http[s]?)|file):)?(\/\/)+([0-9a-zA-Z-_.=?&].+)$/,t=/^((\.|\.\.)?\/)([0-9a-zA-Z-_.=?&]+\/)*([0-9a-zA-Z-_.=?&]+)$/,m=function(i){return _.test(i)||t.test(i)};function d(i,e){var s=i.createElement("style");return s.appendChild(i.createTextNode(e)),s}u.createStyle=d;function l(i,e){var s=i.createElement("link");return s.type="text/css",s.rel="stylesheet",s.href=e,s}u.createLinkStyle=l;function c(i){var e=window.document.createElement("iframe");return e.setAttribute("src","about:blank"),e.setAttribute("style","visibility:hidden;width:0;height:0;position:absolute;z-index:-9999;bottom:0;"),e.setAttribute("width","0"),e.setAttribute("height","0"),e.setAttribute("wmode","opaque"),i.appendChild(e),e}u.createIFrame=c;var p={parent:window.document.body,headElements:[],bodyElements:[]},r=(function(){function i(e){this.isLoading=!1,this.hasEvents=!1,this.opts=[p,e||{}].reduce(function(s,o){return Object.keys(o).forEach(function(n){return s[n]=o[n]}),s},{}),this.iframe=c(this.opts.parent)}return i.prototype.getIFrame=function(){return this.iframe},i.prototype.print=function(e,s,o,n){if(!this.isLoading){var g=this.iframe,x=g.contentDocument,w=g.contentWindow;if(!(!x||!w)&&(this.iframe.src="about:blank",this.elCopy=e.cloneNode(!0),!!this.elCopy)){this.isLoading=!0,this.callback=n;var h=w.document;h.open(),h.write('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body></body></html>'),this.addEvents();var b=this.opts,$=b.headElements,f=b.bodyElements;Array.isArray($)&&$.forEach(function(a){return h.head.appendChild(a)}),Array.isArray(f)&&f.forEach(function(a){return h.body.appendChild(a)}),Array.isArray(s)&&s.forEach(function(a){a&&h.head.appendChild(m(a)?l(h,a):d(h,a))}),h.body.appendChild(this.elCopy),Array.isArray(o)&&o.forEach(function(a){if(a){var v=h.createElement("script");m(a)?v.src=a:v.innerText=a,h.body.appendChild(v)}}),h.close()}}},i.prototype.printURL=function(e,s){this.isLoading||(this.addEvents(),this.isLoading=!0,this.callback=s,this.iframe.src=e)},i.prototype.onBeforePrint=function(e){this.onbeforeprint=e},i.prototype.onAfterPrint=function(e){this.onafterprint=e},i.prototype.launchPrint=function(e){this.isLoading||e.print()},i.prototype.addEvents=function(){var e=this;if(!this.hasEvents){this.hasEvents=!0,this.iframe.addEventListener("load",function(){return e.onLoad()},!1);var s=this.iframe.contentWindow;s&&(this.onbeforeprint&&s.addEventListener("beforeprint",this.onbeforeprint),this.onafterprint&&s.addEventListener("afterprint",this.onafterprint))}},i.prototype.onLoad=function(){var e=this;if(this.iframe){this.isLoading=!1;var s=this.iframe,o=s.contentDocument,n=s.contentWindow;if(!o||!n)return;typeof this.callback=="function"?this.callback({iframe:this.iframe,element:this.elCopy,launchPrint:function(){return e.launchPrint(n)}}):this.launchPrint(n)}},i})();return u.Printd=r,u.default=r,u}var E=S();class y{static ticket(t){if(!t)return;const m=t.date||"",d=(t.time||"").substring(0,8),l=t.mesa||"MESA",c=t.pago||"EFECTIVO",p=t.numero||"",r=t.llamada||"",i=t.user?.name||"",e=t.comment||"",s=t.type||"INGRESO",o=t.detalles||t.details||[];let n="",g=0;o.forEach(f=>{const a=Number(f.qty||f.quantity||0),v=Number(f.price||0),A=Number(f.subtotal||a*v);g+=A,n+=`
        <tr>
          <td class="col-cant">${a}</td>
          <td class="col-detalle">${(f.name||f.product||"").toUpperCase()}</td>
          <td class="col-pu">${v.toFixed(0)}</td>
          <td class="col-total">${A.toFixed(0)}</td>
        </tr>`});const x=Number(t.total||g||0),w=`${window.location.origin}/chicken-logo.png`,h=`
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
          ${s==="INGRESO"&&r?`<div class="llamada-num">${r}</div>`:""}
          <div class="logo">
            <img src="${w}" alt="Chicken's Garden">
          </div>
          <div class="center nombre-local">CHICKEN'S GARDEN</div>
          <div class="center contacto">CONTACTOS: 77909517</div>
          <div class="center direccion">Mercado Campero - Calle 6 N° 21</div>
          ${s==="INGRESO"&&t.name&&t.name!=="SN"?`<div class="cliente-nombre">${t.name}</div>`:""}
          <div class="fecha-hora">
            <span>${m}</span>
            <span>${d}</span>
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
              ${n||'<tr><td colspan="4" style="text-align:center;">SIN DETALLE</td></tr>'}
            </tbody>
          </table>
          <div class="total-section">
            <div class="total-row">
              <span>TOTAL:</span>
              <span>${x.toFixed(2)}</span>
            </div>
            <div class="total-row">
              <span>Pago:</span>
              <span>${c}</span>
            </div>
          </div>
          <div class="ticket-line">
            TICKET ${p} <span class="mesa">${l}</span>
          </div>
          <div class="box-firma">
          ${e?`<div style="margin-top:4px;">${e}</div>`:""}
</div>
          ${s==="INGRESO"?'<div class="pie">GRACIAS POR SU COMPRA, BUEN PROVECHO</div>':""}
          <div class="usuario">
            Usuario: ${i}
          </div>
        </div>
      </div>
    `,b=y._getArea();b.innerHTML=h,new E.Printd().print(b)}static _getArea(){let t=document.getElementById("myelement");return t||(t=document.createElement("div"),t.id="myelement",t.style.position="fixed",t.style.left="-10000px",t.style.top="-10000px",document.body.appendChild(t)),t}static cierreCaja(t){if(!t)return;const m=t.date||"",d=t.user?.name||"",l=Number(t.total_ingresos||0),c=Number(t.total_egresos||0),p=Number(t.total_caja_inicial||0),r=Number(t.tickets||0),i=Number(t.monto_sistema||0),e=Number(t.monto_efectivo||0),s=Number(t.monto_qr||0),o=Number(t.diferencia||0),n=t.observacion||"",g=Number(t.ingresos_efectivo??l),x=Number(t.ingresos_qr||0),w=Number(t.ingresos_tarjeta||0),h=Number(t.ingresos_online||0),b=Number(t.esperado_total??i+x),$=Number(t.contado_total??e+s),f=e-i,a=s-x,v=o||$-b,k=`
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
        <img src="${`${window.location.origin}/chicken-logo.png`}" alt="Chicken's Garden">
      </div>
      <div class="titulo">CIERRE DE CAJA</div>
      <div class="center">Fecha: ${m}</div>
      <div class="center">Usuario cierre: ${d}</div>
      <hr>

      <!-- RESUMEN POR MÉTODO DE PAGO (SISTEMA) -->
      <div class="resumen-row"><span>Ing. EFECTIVO:</span><span>${g.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Ing. QR:</span><span>${x.toFixed(2)} Bs</span></div>
      <!--div class="resumen-row"><span>Ing. TARJETA:</span><span>${w.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Ing. ONLINE:</span><span>${h.toFixed(2)} Bs</span></div-->
      <hr>

      <!-- RESUMEN SISTEMA SOLO EFECTIVO -->
      <!--div class="resumen-row"><span>Caja inicial:</span><span>${p.toFixed(2)} Bs</span></div-->
      <div class="resumen-row"><span>Ingresos caja (efectivo):</span><span>${g.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Egresos:</span><span>${c.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Tickets:</span><span>${r}</span></div>
      <hr>
      <div class="resumen-row"><span>Sistema (efectivo):</span><span>${i.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Efectivo contado:</span><span>${e.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Dif. efectivo:</span><span>${f.toFixed(2)} Bs</span></div>
      <hr>

      <!-- QR -->
      <div class="resumen-row"><span>QR esperado:</span><span>${x.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>QR contado:</span><span>${s.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Dif. QR:</span><span>${a.toFixed(2)} Bs</span></div>
      <hr>

      <!-- TOTALES -->
      <div class="resumen-row"><span>Total esperado:</span><span>${b.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Total contado:</span><span>${$.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Diferencia total:</span><span>${v.toFixed(2)} Bs</span></div>

      ${n?`<div class="pie" style="margin-top:4px;">Obs: ${n}</div>`:""}
      <hr>
      <div class="pie">Gracias por su trabajo</div>
      <div class="usuario">Firmado: ____________________</div>
    </div>
  `,N=y._getArea();N.innerHTML=k,new E.Printd().print(N)}static reporteUsuarios(t){const m=t?.usuarios||[],d=t?.date_from||"",l=t?.date_to||"",c=`${window.location.origin}/chicken-logo.png`;let p="",r=0;m.forEach(o=>{const n=Number(o.neto||0);r+=n,p+=`
      <tr>
        <td>${o.user_name}</td>
        <td class="num">${Number(o.total_ingresos||0).toFixed(2)}</td>
        <td class="num">${Number(o.total_egresos||0).toFixed(2)}</td>
        <!--td class="num">${Number(o.total_caja||0).toFixed(2)}</td!-->
        <td class="num">${n.toFixed(2)}</td>
        <td class="num">${Number(o.tickets||0)}</td>
      </tr>
    `});const i=`
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
        <img src="${c}" alt="Chicken's Garden">
      </div>
      <div class="center" style="font-weight:bold;">RESUMEN DE VENTAS POR USUARIO</div>
      <div class="center">Desde: ${d||"-"} Hasta: ${l||"-"}</div>
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
          ${p||'<tr><td colspan="6" class="center">Sin datos</td></tr>'}
        </tbody>
      </table>
      <div class="pie">Total neto: ${r.toFixed(2)} Bs</div>
    </div>
  `,e=y._getArea();e.innerHTML=i,new E.Printd().print(e)}static reporteProductosPorUsuario(t){const m=t?.productos||[],d=`${window.location.origin}/chicken-logo.png`;let l="";m.forEach(i=>{const e=i.user_name;let s="";i.items.forEach(o=>{s+=`
        <tr>
          <td>${o.name}</td>
          <td class="num">${Number(o.qty||0)}</td>
          <td class="num">${Number(o.subtotal||0).toFixed(2)}</td>
        </tr>
      `}),l+=`
      <div class="user-block">
        <div class="user-title">Usuario: ${e}</div>
        <table>
          <thead>
            <tr>
              <th>Producto</th>
              <th>Cant.</th>
              <th>Total Bs</th>
            </tr>
          </thead>
          <tbody>
            ${s||'<tr><td colspan="3">Sin productos</td></tr>'}
          </tbody>
        </table>
      </div>
      <hr>
    `});const c=`
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
        <img src="${d}" alt="Chicken's Garden">
      </div>
      <div class="center" style="font-weight:bold;">PRODUCTOS POR USUARIO</div>
      <hr>
      ${l||'<div class="center">Sin datos</div>'}
    </div>
  `,p=y._getArea();p.innerHTML=c,new E.Printd().print(p)}static reporteVentasPorUsuario(t){const m=t?.ventas||[],d=t?.usuarios&&t.usuarios[0]||null,l=`${window.location.origin}/chicken-logo.png`,c=t?.date_from||"",p=t?.date_to||"";let r="",i=0;m.forEach(n=>{const g=Number(n.total||0);i+=g,r+=`
        <tr>
          <td>${n.numero}</td>
          <td>${n.date}</td>
          <td>${String(n.time||"").substring(0,8)}</td>
          <td>${n.mesa}</td>
          <td>${n.pago}</td>
          <td class="num">${g.toFixed(2)}</td>
        </tr>
      `});const e=`
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
        <img src="${l}" alt="Chicken's Garden">
      </div>
      <div class="center titulo">VENTAS POR USUARIO</div>
      <div class="center">
        Usuario: ${d?d.user_name:""}
      </div>
      <div class="center">
        Desde: ${c||"-"} Hasta: ${p||"-"}
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
          ${r||'<tr><td colspan="6" class="center">Sin ventas</td></tr>'}
        </tbody>
      </table>
      <div class="center" style="margin-top:4px;">
        Total ventas: ${i.toFixed(2)} Bs
      </div>
    </div>
  `,s=y._getArea();s.innerHTML=e,new E.Printd().print(s)}static reporteUltimoCierreUsuarios(t){const m=`${window.location.origin}/chicken-logo.png`,d=t.date||"",l=t.usuarios||[];let c="";l.forEach(i=>{c+=`
      <tr>
        <td>${i.user_name}</td>
        <td class="num">${i.efectivo.toFixed(2)}</td>
        <td class="num">${i.qr.toFixed(2)}</td>
        <td class="num">${i.total.toFixed(2)}</td>
        <td class="num">${i.tickets}</td>
      </tr>
    `});const p=`
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
      <img src="${m}" width="70"><br>
      <b>REPORTE ÚLTIMO CIERRE DE CAJA</b><br>
      Fecha: ${d}
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
        ${c||'<tr><td colspan="5" class="center">Sin datos</td></tr>'}
      </tbody>
    </table>

    <div class="center" style="margin-top:5px;">
      TOTAL GENERAL: ${Number(t.total||0).toFixed(2)} Bs
    </div>
  </div>
  `,r=y._getArea();r.innerHTML=p,new E.Printd().print(r)}}export{y as I};
