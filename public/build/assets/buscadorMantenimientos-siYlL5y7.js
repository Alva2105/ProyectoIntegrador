document.addEventListener("DOMContentLoaded",function(){const n=document.getElementById("searchInput"),i=document.getElementById("searchIcon");if(!n||!i)return;const s=document.querySelector("table tbody"),f=document.querySelector('meta[name="csrf-token"]').content;async function d(){try{const o=await(await fetch("/dashboard/mantenimientos")).text(),a=document.createElement("div");a.innerHTML=o;const t=a.querySelector("table tbody");t&&(s.innerHTML=t.innerHTML)}catch(e){console.error("Error al recargar tabla:",e)}}async function l(){const e=n.value.trim();if(e===""){await d();return}try{const a=await(await fetch(`/dashboard/mantenimientos/buscar?q=${encodeURIComponent(e)}`,{headers:{"X-CSRF-TOKEN":f}})).json();if(s.innerHTML="",a.length===0){s.innerHTML='<tr><td colspan="9" style="text-align:center; color:#666;">No se encontraron resultados</td></tr>';return}a.forEach(t=>{var u,m,b,p;const c=(m=(u=t.cliente)==null?void 0:u.usuario)==null?void 0:m.registro,r=(b=t.cliente)==null?void 0:b.usuario,y=c?`
                        <div class="perfil-cliente">
                            ${r!=null&&r.img_usu&&r.img_usu!=="NULL"?`<img src="/storage/${r.img_usu}" alt="Perfil" class="img-mini">`:'<span class="material-symbols-outlined icono-perfil">account_circle</span>'}
                            <span class="nombre-cliente">
                                ${c.nom_reg??""} ${c.apa_reg??""} ${c.ama_reg??""}
                            </span>
                        </div>`:'<span class="material-symbols-outlined icono-perfil">account_circle</span> Sin asignar';s.innerHTML+=`
                    <tr data-id="${t.cod_man}">
                        <td>${t.cod_man}</td>
                        <td>${y}</td>
                        <td>${t.tma_man??"Desconocido"}</td>
                        <td><span class="estado ${((p=t.est_man)==null?void 0:p.toLowerCase())??"sin-estado"}">${t.est_man??"Sin estado"}</span></td>
                        <td>${t.fen_man??"-"}</td>
                        <td>${t.ffi_man??"-"}</td>
                        <td>${t.des_man??"-"}</td>
                        <td>
                            <button class="btn-editar" onclick="activarEdicionEstadoMantenimiento(this)">Editar</button>
                            <button class="btn-guardar" style="display:none;" onclick="guardarEstadoMantenimiento(this)">Guardar</button>
                            <button class="btn-eliminar">Eliminar</button>
                        </td>
                    </tr>
                `})}catch(o){console.error("Error al buscar mantenimientos:",o),alert("❌ Error al buscar mantenimientos.")}}i.addEventListener("click",l),n.addEventListener("keypress",e=>{e.key==="Enter"&&l()}),n.addEventListener("input",async function(){n.value.trim()===""&&await d()})});
