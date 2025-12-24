var u={},_;function N(){if(_)return u;_=1,Object.defineProperty(u,"__esModule",{value:!0}),u.Printd=u.createIFrame=u.createLinkStyle=u.createStyle=void 0;var k=/^(((http[s]?)|file):)?(\/\/)+([0-9a-zA-Z-_.=?&].+)$/,t=/^((\.|\.\.)?\/)([0-9a-zA-Z-_.=?&]+\/)*([0-9a-zA-Z-_.=?&]+)$/,h=function(o){return k.test(o)||t.test(o)};function d(o,e){var i=o.createElement("style");return i.appendChild(o.createTextNode(e)),i}u.createStyle=d;function l(o,e){var i=o.createElement("link");return i.type="text/css",i.rel="stylesheet",i.href=e,i}u.createLinkStyle=l;function m(o){var e=window.document.createElement("iframe");return e.setAttribute("src","about:blank"),e.setAttribute("style","visibility:hidden;width:0;height:0;position:absolute;z-index:-9999;bottom:0;"),e.setAttribute("width","0"),e.setAttribute("height","0"),e.setAttribute("wmode","opaque"),o.appendChild(e),e}u.createIFrame=m;var c={parent:window.document.body,headElements:[],bodyElements:[]},r=(function(){function o(e){this.isLoading=!1,this.hasEvents=!1,this.opts=[c,e||{}].reduce(function(i,n){return Object.keys(n).forEach(function(a){return i[a]=n[a]}),i},{}),this.iframe=m(this.opts.parent)}return o.prototype.getIFrame=function(){return this.iframe},o.prototype.print=function(e,i,n,a){if(!this.isLoading){var g=this.iframe,x=g.contentDocument,y=g.contentWindow;if(!(!x||!y)&&(this.iframe.src="about:blank",this.elCopy=e.cloneNode(!0),!!this.elCopy)){this.isLoading=!0,this.callback=a;var p=y.document;p.open(),p.write('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body></body></html>'),this.addEvents();var w=this.opts,E=w.headElements,f=w.bodyElements;Array.isArray(E)&&E.forEach(function(s){return p.head.appendChild(s)}),Array.isArray(f)&&f.forEach(function(s){return p.body.appendChild(s)}),Array.isArray(i)&&i.forEach(function(s){s&&p.head.appendChild(h(s)?l(p,s):d(p,s))}),p.body.appendChild(this.elCopy),Array.isArray(n)&&n.forEach(function(s){if(s){var b=p.createElement("script");h(s)?b.src=s:b.innerText=s,p.body.appendChild(b)}}),p.close()}}},o.prototype.printURL=function(e,i){this.isLoading||(this.addEvents(),this.isLoading=!0,this.callback=i,this.iframe.src=e)},o.prototype.onBeforePrint=function(e){this.onbeforeprint=e},o.prototype.onAfterPrint=function(e){this.onafterprint=e},o.prototype.launchPrint=function(e){this.isLoading||e.print()},o.prototype.addEvents=function(){var e=this;if(!this.hasEvents){this.hasEvents=!0,this.iframe.addEventListener("load",function(){return e.onLoad()},!1);var i=this.iframe.contentWindow;i&&(this.onbeforeprint&&i.addEventListener("beforeprint",this.onbeforeprint),this.onafterprint&&i.addEventListener("afterprint",this.onafterprint))}},o.prototype.onLoad=function(){var e=this;if(this.iframe){this.isLoading=!1;var i=this.iframe,n=i.contentDocument,a=i.contentWindow;if(!n||!a)return;typeof this.callback=="function"?this.callback({iframe:this.iframe,element:this.elCopy,launchPrint:function(){return e.launchPrint(a)}}):this.launchPrint(a)}},o})();return u.Printd=r,u.default=r,u}var $=N();class v{static ticket(t){if(!t)return;const h=t.date||"",d=(t.time||"").substring(0,8),l=t.mesa||"MESA",m=t.pago||"EFECTIVO",c=t.numero||"",r=t.llamada||"",o=t.user?.name||"",e=t.comment||"",i=t.type||"INGRESO",n=t.detalles||t.details||[];let a="",g=0;n.forEach(f=>{const s=Number(f.qty||f.quantity||0),b=Number(f.price||0),A=Number(f.subtotal||s*b);g+=A,a+=`
        <tr>
          <td class="col-cant">${s}</td>
          <td class="col-detalle">${(f.name||f.product||"").toUpperCase()}</td>
          <td class="col-pu">${b.toFixed(0)}</td>
          <td class="col-total">${A.toFixed(0)}</td>
        </tr>`});const x=Number(t.total||g||0),y=`${window.location.origin}/chicken-logo.png`,p=`
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
          ${i==="INGRESO"&&r?`<div class="llamada-num">${r}</div>`:""}
          <div class="logo">
            <img src="${y}" alt="Chicken's Garden">
          </div>
          <div class="center nombre-local">CHICKEN'S GARDEN</div>
          <div class="center contacto">CONTACTOS: 77909517</div>
          <div class="center direccion">Mercado Campero - Calle 6 N° 21</div>
          ${i==="INGRESO"&&t.name&&t.name!=="SN"?`<div class="cliente-nombre">${t.name}</div>`:""}
          <div class="fecha-hora">
            <span>${h}</span>
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
              ${a||'<tr><td colspan="4" style="text-align:center;">SIN DETALLE</td></tr>'}
            </tbody>
          </table>
          <div class="total-section">
            <div class="total-row">
              <span>TOTAL:</span>
              <span>${x.toFixed(2)}</span>
            </div>
            <div class="total-row">
              <span>Pago:</span>
              <span>${m}</span>
            </div>
          </div>
          <div class="ticket-line">
            TICKET ${c} <span class="mesa">${l}</span>
          </div>
          ${e?`<div class="pie" style="margin-top:4px;">${e}</div>`:""}
          <div class="box-firma"></div>
          ${i==="INGRESO"?'<div class="pie">GRACIAS POR SU COMPRA, BUEN PROVECHO</div>':""}
          <div class="usuario">
            Usuario: ${o}
          </div>
        </div>
      </div>
    `,w=v._getArea();w.innerHTML=p,new $.Printd().print(w)}static _getArea(){let t=document.getElementById("myelement");return t||(t=document.createElement("div"),t.id="myelement",t.style.position="fixed",t.style.left="-10000px",t.style.top="-10000px",document.body.appendChild(t)),t}static cierreCaja(t){if(!t)return;const h=t.date||"",d=t.user?.name||"",l=Number(t.total_ingresos||0),m=Number(t.total_egresos||0),c=Number(t.total_caja_inicial||0),r=Number(t.tickets||0),o=Number(t.monto_sistema||0),e=Number(t.monto_efectivo||0),i=Number(t.diferencia||0),n=t.observacion||"",g=`
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
      <div class="center">Fecha: ${h}</div>
      <div class="center">Usuario: ${d}</div>
      <hr>
      <div class="resumen-row"><span>Ingresos:</span><span>${l.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Egresos:</span><span>${m.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Caja inicial:</span><span>${c.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Tickets:</span><span>${r}</span></div>
      <hr>
      <div class="resumen-row"><span>Sistema:</span><span>${o.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Efectivo contado:</span><span>${e.toFixed(2)} Bs</span></div>
      <div class="resumen-row"><span>Diferencia:</span><span>${i.toFixed(2)} Bs</span></div>
      ${n?`<div class="pie" style="margin-top:4px;">Obs: ${n}</div>`:""}
      <hr>
      <div class="pie">Gracias por su trabajo</div>
      <div class="usuario">Firmado: ____________________</div>
    </div>
  `,x=v._getArea();x.innerHTML=g,new $.Printd().print(x)}static reporteUsuarios(t){const h=t?.usuarios||[],d=t?.date_from||"",l=t?.date_to||"",m=`${window.location.origin}/chicken-logo.png`;let c="",r=0;h.forEach(n=>{const a=Number(n.neto||0);r+=a,c+=`
      <tr>
        <td>${n.user_name}</td>
        <td class="num">${Number(n.total_ingresos||0).toFixed(2)}</td>
        <td class="num">${Number(n.total_egresos||0).toFixed(2)}</td>
        <!--td class="num">${Number(n.total_caja||0).toFixed(2)}</td!-->
        <td class="num">${a.toFixed(2)}</td>
        <td class="num">${Number(n.tickets||0)}</td>
      </tr>
    `});const o=`
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
        <img src="${m}" alt="Chicken's Garden">
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
          ${c||'<tr><td colspan="6" class="center">Sin datos</td></tr>'}
        </tbody>
      </table>
      <div class="pie">Total neto: ${r.toFixed(2)} Bs</div>
    </div>
  `,e=v._getArea();e.innerHTML=o,new $.Printd().print(e)}static reporteProductosPorUsuario(t){const h=t?.productos||[],d=`${window.location.origin}/chicken-logo.png`;let l="";h.forEach(o=>{const e=o.user_name;let i="";o.items.forEach(n=>{i+=`
        <tr>
          <td>${n.name}</td>
          <td class="num">${Number(n.qty||0)}</td>
          <td class="num">${Number(n.subtotal||0).toFixed(2)}</td>
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
            ${i||'<tr><td colspan="3">Sin productos</td></tr>'}
          </tbody>
        </table>
      </div>
      <hr>
    `});const m=`
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
  `,c=v._getArea();c.innerHTML=m,new $.Printd().print(c)}static reporteVentasPorUsuario(t){const h=t?.ventas||[],d=t?.usuarios&&t.usuarios[0]||null,l=`${window.location.origin}/chicken-logo.png`,m=t?.date_from||"",c=t?.date_to||"";let r="",o=0;h.forEach(a=>{const g=Number(a.total||0);o+=g,r+=`
        <tr>
          <td>${a.numero}</td>
          <td>${a.date}</td>
          <td>${String(a.time||"").substring(0,8)}</td>
          <td>${a.mesa}</td>
          <td>${a.pago}</td>
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
        Desde: ${m||"-"} Hasta: ${c||"-"}
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
        Total ventas: ${o.toFixed(2)} Bs
      </div>
    </div>
  `,i=v._getArea();i.innerHTML=e,new $.Printd().print(i)}}export{v as I};
