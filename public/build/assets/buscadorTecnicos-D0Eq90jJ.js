document.addEventListener("DOMContentLoaded",function(){const s=document.getElementById("searchTInput"),l=document.getElementById("searchTIcon");if(!s||!l)return;const c=document.querySelector(".tabla-solicitud tbody"),u=document.querySelector('meta[name="csrf-token"]'),m=u?u.content:"";function $(e,n=300){let t;return function(...r){clearTimeout(t),t=setTimeout(()=>e.apply(this,r),n)}}function b(e){if(e===null||typeof e>"u"||e==="")return null;if(e instanceof Date&&!isNaN(e)){const n=e;return`${String(n.getDate()).padStart(2,"0")}/${String(n.getMonth()+1).padStart(2,"0")}/${n.getFullYear()}`}if(typeof e=="number"){const n=e.toString().length===10?e*1e3:e,t=new Date(n);return isNaN(t)?null:`${String(t.getDate()).padStart(2,"0")}/${String(t.getMonth()+1).padStart(2,"0")}/${t.getFullYear()}`}if(typeof e=="string"){const n=e.trim();let t=n.match(/^(\d{4})-(\d{2})-(\d{2})/);if(t)return`${t[3]}/${t[2]}/${t[1]}`;if(t=n.match(/^(\d{4})\/(\d{2})\/(\d{2})/),t)return`${t[3]}/${t[2]}/${t[1]}`;if(t=n.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/),t)return`${t[1]}/${t[2]}/${t[3]}`;if(/^0{4}[-\/]0{2}[-\/]0{2}$/.test(n))return null;const r=new Date(n);if(!isNaN(r))return`${String(r.getDate()).padStart(2,"0")}/${String(r.getMonth()+1).padStart(2,"0")}/${r.getFullYear()}`}return null}async function f(){try{const n=await(await fetch("/gerente/listadoTecnicos")).text(),t=document.createElement("div");t.innerHTML=n;const r=t.querySelector(".tabla-solicitud tbody");r&&(c.innerHTML=r.innerHTML)}catch(e){console.error("Error al recargar tabla original:",e)}}async function d(){const e=s.value.trim();if(e===""){await f();return}try{const n=`/gerente/listadoTecnicos/buscar?q=${encodeURIComponent(e)}`,t=await fetch(n,{headers:{"X-CSRF-TOKEN":m,Accept:"application/json"}});if(!t.ok){const a=await t.text();console.error("Respuesta no OK del servidor:",t.status,a),c.innerHTML='<tr><td colspan="8" style="text-align:center; color:#c00;">Error al buscar técnicos</td></tr>';return}const r=await t.json();if(c.innerHTML="",!Array.isArray(r)||r.length===0){c.innerHTML='<tr><td colspan="8" style="text-align:center; color:#666;">No se encontraron resultados</td></tr>';return}r.forEach((a,T)=>{const i=a.usuario||{},o=i.registro||{},_=i.img_usu&&i.img_usu!=="NULL"?i.img_usu:null,h=`${o.nom_reg??""} ${o.apa_reg??""} ${o.ama_reg??""}`.trim()||"—",S=o.cie_reg??"—",E=a.esp_tau??"—",L=o.coe_reg??"—",g=o.fna_reg??o.fna??null,D=g&&b(g)||"—",p=i.est_usu??"Desconocido",M=T+1;c.innerHTML+=`
                    <tr>
                        <!-- ID -->
                        <td class="td-solicitud">${M}</td>

                        <!-- Nombre completo -->
                        <td class="td-solicitud">${h}</td>

                        <!-- Teléfono (cie_reg) -->
                        <td class="td-solicitud">${S}</td>

                        <!-- Especialidad -->
                        <td class="td-solicitud">${E}</td>

                        <!-- Correo -->
                        <td class="td-solicitud">${L}</td>

                        <!-- Fecha nacimiento -->
                        <td class="td-solicitud">${D}</td>

                        <!-- Estado -->
                        <td class="td-solicitud">
                            <span class="estado ${String(p).toLowerCase()}">${p}</span>
                        </td>

                        <!-- Acción -->
                        <td class="td-solicitud">
                            <button class="btn-aprobar" data-id="${a.cod_tau??""}">Aprobar</button>
                        </td>
                    </tr>
                `})}catch(n){console.error("Error al buscar técnicos:",n),c.innerHTML='<tr><td colspan="8" style="text-align:center; color:#c00;">Error al buscar técnicos</td></tr>'}}const y=$(d,250);l.addEventListener("click",function(e){e.preventDefault(),d()}),s.addEventListener("keypress",e=>{e.key==="Enter"&&(e.preventDefault(),d())}),s.addEventListener("input",async()=>{s.value.trim()===""?await f():y()})});
