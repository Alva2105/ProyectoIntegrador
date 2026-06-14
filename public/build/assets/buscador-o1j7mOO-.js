document.addEventListener("DOMContentLoaded",function(){const r=document.getElementById("searchInput"),c=document.getElementById("searchIcon");if(!r||!c)return;const o=document.querySelector("table tbody"),l=document.querySelector('meta[name="csrf-token"]').content;async function i(){try{const s=await(await fetch("/dashboard/usuarios")).text(),a=document.createElement("div");a.innerHTML=s;const t=a.querySelector("table tbody");t&&(o.innerHTML=t.innerHTML)}catch(e){console.error("Error al recargar tabla:",e)}}async function u(){const e=r.value.trim();if(e===""){await i();return}try{const a=await(await fetch(`/dashboard/usuarios/buscar?q=${encodeURIComponent(e)}`,{headers:{"X-CSRF-TOKEN":l}})).json();if(o.innerHTML="",a.length===0){o.innerHTML='<tr><td colspan="6" style="text-align:center; color:#666;">No se encontraron resultados</td></tr>';return}a.forEach(t=>{const n=t.usuario||{},m=n.rol?n.rol.nom_rol:"Sin rol",d=n.est_usu||"Sin estado",b=n.img_usu&&n.img_usu!=="NULL"?`<img src="/storage/${n.img_usu}" alt="Perfil" class="img-mini">`:'<span class="material-symbols-outlined icono-perfil">account_circle</span>';o.innerHTML+=`
                    <tr>
                        <td class="id-usuario">${b} ${t.cod_reg}</td>
                        <td>${t.nom_reg} ${t.apa_reg??""} ${t.ama_reg??""}</td>
                        <td>${t.coe_reg}</td>
                        <td>${m}</td>
                        <td><span class="estado ${d.toLowerCase()}">${d}</span></td>
                        <td>
                            <button class="btn-editar">Editar</button>
                            <button class="btn-eliminar">Eliminar</button>
                        </td>
                    </tr>`})}catch(s){console.error("Error al buscar:",s),alert("❌ Error al buscar usuarios")}}c.addEventListener("click",u),r.addEventListener("keypress",e=>{e.key==="Enter"&&u()}),r.addEventListener("input",async function(){r.value.trim()===""&&await i()})});
