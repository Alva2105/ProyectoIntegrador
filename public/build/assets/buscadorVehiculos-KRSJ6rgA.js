document.addEventListener("DOMContentLoaded",function(){const n=document.getElementById("searchInput"),r=document.getElementById("searchIcon");if(!n||!r)return;const a=document.querySelector("table tbody");async function i(){location.reload()}async function d(){const e=n.value.trim();if(e===""){await i();return}try{const c=await(await fetch(`/dashboard/vehiculos/buscar?q=${encodeURIComponent(e)}`)).json();if(a.innerHTML="",c.length===0){a.innerHTML=`<tr>
                        <td colspan="9" style="text-align:center;">
                            No se encontraron resultados
                        </td>
                    </tr>`;return}c.forEach(t=>{a.innerHTML+=`
                    <tr>

                        <td>${t.cod_vehiculos}</td>

                        <td>${t.pla_veh}</td>

                        <td>${t.mar_veh??"-"}</td>

                        <td>${t.mod_veh??"-"}</td>

                        <td>${t.ani_veh??"-"}</td>

                        <td>${t.col_veh??"-"}</td>

                        <td>${t.tip_veh??"-"}</td>

                        <td>
                            ${t.cliente?t.cliente.nom_cli+" "+t.cliente.app_cli:"Sin cliente"}
                        </td>

                        <td class="acciones">

                            <button
                                class="btn-editar"
                                onclick="editarVehiculo(
                                    '${t.cod_vehiculos}',
                                    '${t.cod_clientes_veh}',
                                    '${t.pla_veh}',
                                    '${t.mar_veh}',
                                    '${t.mod_veh}',
                                    '${t.ani_veh}',
                                    '${t.col_veh}',
                                    '${t.tip_veh}'
                                )">
                                Editar
                            </button>

                            <form method="POST"
                                  action="/dashboard/vehiculos/${t.cod_vehiculos}/${t.cod_clientes_veh}/eliminar"
                                  style="display:inline-block;"
                                  onsubmit="return confirm('¿Eliminar vehículo?')">

                                <input type="hidden" name="_token"
                                       value="${document.querySelector('meta[name="csrf-token"]').content}">

                                <input type="hidden" name="_method" value="DELETE">

                                <button type="submit"
                                        class="btn-eliminar">
                                    Eliminar
                                </button>

                            </form>

                        </td>

                    </tr>
                `})}catch(o){console.error(o)}}r.addEventListener("click",d),n.addEventListener("keypress",function(e){e.key==="Enter"&&d()})});
