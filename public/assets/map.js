'use strict';
(() => {
 const status=document.querySelector('#map-status');
 if(typeof L==='undefined'){if(status)status.textContent='Map library is unavailable. Property records remain available in the register.';return;}
 function legacyTiles(map,el){
  const notice=document.createElement('div');notice.className='tile-notice';notice.hidden=true;notice.setAttribute('role','status');
  const message=document.createElement('span');const retry=document.createElement('button');retry.type='button';retry.className='button small';retry.textContent='Retry base map';notice.append(message,retry);el.before(notice);
  const show=text=>{message.textContent=text;notice.hidden=false;};
  if(!el.dataset.tiles){show('No base-map provider is configured. Parcel boundaries remain available.');retry.hidden=true;return;}
  let errors=0;
  const layer=L.tileLayer(el.dataset.tiles,{maxZoom:20,maxNativeZoom:19,attribution:el.dataset.attribution||'© OpenStreetMap contributors'});
  layer.on('loading',()=>{errors=0;});
  layer.on('tileerror',()=>{errors++;show('Base-map tiles are unavailable. You can still search and open property boundaries.');});
  layer.on('load',()=>{if(!errors)notice.hidden=true;});
  retry.addEventListener('click',()=>{errors=0;layer.redraw();});layer.addTo(map);return layer;
 }
 function configuredTiles(map,el){
  let config;try{config=JSON.parse(el.dataset.mapConfig||'{}');}catch{config={};}
  const notice=document.createElement('div');notice.className='tile-notice';notice.hidden=true;notice.setAttribute('role','status');
  const message=document.createElement('span');notice.append(message);el.before(notice);
  const baseLayers={},layers={};let selected=null;
  const make=(key,source)=>{
   if(!source?.url)return null;
   let errors=0;const layer=L.tileLayer(source.url,{maxZoom:Number(source.maxZoom||20),maxNativeZoom:Number(source.maxNativeZoom||source.maxZoom||19),minZoom:Number(source.minZoom||0),attribution:source.attribution||''});
   layer.on('loading',()=>{errors=0;});layer.on('tileerror',()=>{errors++;message.textContent=`${source.name} tiles are unavailable. Parcel boundaries and other map sources remain usable.`;notice.hidden=false;});layer.on('load',()=>{if(!errors)notice.hidden=true;});layers[key]=layer;return layer;
  };
  for(const [key,source] of Object.entries(config.basemaps||{})){const layer=make(key,source);if(layer)baseLayers[source.name||key]=layer;}
  const preferred=config.default==='esri'?'Esri World Imagery':'OpenStreetMap';selected=baseLayers[preferred]||Object.values(baseLayers)[0];if(selected)selected.addTo(map);else{message.textContent='No base map is configured. Parcel boundaries remain usable.';notice.hidden=false;}
  let orthophoto=null;if(config.orthophoto?.url){orthophoto=make('orthophoto',config.orthophoto);if(orthophoto){orthophoto.setOpacity(Number(config.orthophoto.opacity||1));baseLayers[config.orthophoto.name||'EDDT Orthophoto']=orthophoto;}}
  const control=L.control.layers(baseLayers,{}, {collapsed:false}).addTo(map);
  const container=control.getContainer(),list=container.querySelector('.leaflet-control-layers-list');
  container.classList.add('map-layer-dropdown');
  const button=document.createElement('button');button.type='button';button.className='map-layer-trigger';button.setAttribute('aria-expanded','false');button.setAttribute('aria-controls','map-layer-options');list.id='map-layer-options';list.hidden=true;
  const selectedName=()=>Object.entries(baseLayers).find(([,layer])=>map.hasLayer(layer))?.[0]||'Map layers';
  const label=()=>{button.textContent=selectedName()+' ▾';button.setAttribute('aria-label','Map layers: '+selectedName());};
  const toggle=open=>{list.hidden=!open;button.setAttribute('aria-expanded',String(open));};
  container.prepend(button);label();let focusOpened=false;
  button.addEventListener('focus',()=>{if(list.hidden){toggle(true);focusOpened=true;setTimeout(()=>focusOpened=false,200);}});
  button.addEventListener('click',()=>{if(!focusOpened)toggle(list.hidden);focusOpened=false;});
  container.addEventListener('keydown',event=>{if(event.key==='Escape'){event.preventDefault();event.stopPropagation();button.focus();toggle(false);}if(event.key==='ArrowDown'&&event.target===button){event.preventDefault();toggle(true);list.querySelector('input')?.focus();}});
  document.addEventListener('pointerdown',event=>{if(!container.contains(event.target))toggle(false);});
  container.addEventListener('focusout',()=>setTimeout(()=>{if(!container.contains(document.activeElement))toggle(false);},0));
  map.on('baselayerchange',()=>{label();button.focus();toggle(false);});
  L.DomEvent.disableClickPropagation(container);L.DomEvent.disableScrollPropagation(container);
  const opacity=document.querySelector('#orthophoto-opacity'),opacityWrap=document.querySelector('#orthophoto-opacity-wrap');
  if(orthophoto&&opacity&&opacityWrap){opacity.value=String(Math.round(Number(config.orthophoto.opacity||1)*100));opacityWrap.hidden=false;opacity.addEventListener('input',()=>orthophoto.setOpacity(Number(opacity.value)/100));}
  map.on('baselayerchange',event=>{try{localStorage.setItem('eddt.map.basemap',event.name);}catch{}});
  try{const remembered=localStorage.getItem('eddt.map.basemap');if(remembered&&baseLayers[remembered]&&baseLayers[remembered]!==selected){map.removeLayer(selected);baseLayers[remembered].addTo(map);selected=baseLayers[remembered];}}catch{}label();
  return {baseLayers,control,orthophoto};
 }
 function popupContent(p,layer){
  const resize=()=>{const popup=layer.getPopup();if(popup?.isOpen())popup.update();};
  const make=(tag,text,className)=>{const el=document.createElement(tag);if(text!==undefined)el.textContent=text;if(className)el.className=className;return el;};
  const popup=make('section',undefined,'parcel-popup');popup.append(make('h3',p.property_number||p.parcel_number));
  const badge=make('span',p.payment_label||'Not yet billed','payment-badge payment-'+(p.payment_status||'unbilled'));popup.append(badge);
  const ownerBox=make('div',undefined,'parcel-owners');ownerBox.append(make('h4','Owners'),make('p',p.owners||'Owner not yet recorded'));popup.append(ownerBox);
  if(p.locality||p.land_id)popup.append(make('p',[p.locality,p.land_id].filter(Boolean).join(' · '),'parcel-location'));
  if(Number(p.bill_count)>0){const dl=make('dl',undefined,'parcel-balances');for(const [label,key]of [['Billed','billed'],['Paid','paid'],['Balance','outstanding']])dl.append(make('dt',label),make('dd','GHS '+Number(p[key]||0).toLocaleString('en-GH',{minimumFractionDigits:2,maximumFractionDigits:2})));popup.append(dl,make('p','All issued bills, including arrears.','parcel-note'));if(p.payment_status==='exempt'&&Number(p.outstanding)>0)popup.append(make('p','Exemption does not cancel the existing balance.','parcel-note'));}
  else popup.append(make('p','No bills have been issued.','parcel-note'));
  const gallery=make('div',undefined,'parcel-gallery');const loading=make('p',p.preview_url?'Loading property photos…':'No property photos recorded.','photo-placeholder');loading.setAttribute('role','status');gallery.append(loading);popup.append(gallery);
  const link=make('a','Open property record →','popup-record-link');link.href=p.url;popup.append(link);
  if(p.preview_url)fetch(p.preview_url,{headers:{Accept:'application/json'}}).then(async response=>{if(!response.ok)throw Error();return response.json();}).then(data=>{
   ownerBox.replaceChildren(make('h4','Owners'));if(!data.owners.length)ownerBox.append(make('p','Owner not yet recorded'));
   for(const owner of data.owners){const name=owner.organization_name?[owner.organization_name,owner.full_name].filter((v,i,a)=>v&&a.indexOf(v)===i).join(' — '):owner.full_name;ownerBox.append(make('p',name+(owner.ownership_percentage?' ('+Number(owner.ownership_percentage)+'%)':'')));}
   const photos=data.photos;if(!photos.length){loading.textContent='No property photos available.';resize();return;}
   gallery.replaceChildren();const image=make('img');image.decoding='async';image.loading='lazy';const caption=make('p',undefined,'photo-caption'),failure=make('p','This photograph is unavailable.','photo-placeholder');failure.hidden=true;gallery.append(image,failure,caption);
   let index=0;const controls=make('div',undefined,'photo-controls'),previous=make('button','Previous'),next=make('button','Next'),counter=make('span');previous.type=next.type='button';previous.setAttribute('aria-label','Previous property photograph');next.setAttribute('aria-label','Next property photograph');counter.setAttribute('aria-live','polite');
   function show(){const photo=photos[index];image.hidden=false;failure.hidden=true;image.alt=photo.caption||'Property '+(p.property_number||p.parcel_number);image.src=photo.url;caption.textContent=[photo.caption,photo.taken_at?.slice(0,10)].filter(Boolean).join(' · ');counter.textContent=(index+1)+' / '+photos.length;previous.disabled=index===0;next.disabled=index===photos.length-1;}
   image.addEventListener('load',resize);image.addEventListener('error',()=>{image.hidden=true;failure.hidden=false;resize();});previous.addEventListener('click',()=>{if(index>0){index--;show();}});next.addEventListener('click',()=>{if(index<photos.length-1){index++;show();}});controls.append(previous,counter,next);if(photos.length>1)gallery.append(controls);show();resize();
  }).catch(()=>{loading.textContent='Could not load owners and photos. Open the property record or sign in again.';resize();});
  return popup;
 }
 const root=document.querySelector('#property-map');
 if(root){
  const map=L.map(root,{preferCanvas:true}).setView([5.61,-0.13],12);const tileConfig=configuredTiles(map,root);
  const panel=root.closest('.panel');
  const expand=document.createElement('button');expand.type='button';expand.className='button';expand.id='map-expand';expand.textContent='Full screen';expand.setAttribute('aria-pressed','false');expand.setAttribute('aria-controls','property-map');panel.querySelector('.toolbar').append(expand);
  let fallbackExpanded=false;
  function syncExpanded(){
   const active=document.fullscreenElement===panel||fallbackExpanded;
   panel.classList.toggle('map-expanded',active);document.body.classList.toggle('map-is-expanded',active);
   expand.textContent=active?'Exit full screen':'Full screen';expand.setAttribute('aria-pressed',String(active));
   requestAnimationFrame(()=>map.invalidateSize({pan:true,animate:false}));
  }
  async function closeExpanded(){
   if(document.fullscreenElement===panel)await document.exitFullscreen();
   fallbackExpanded=false;syncExpanded();expand.focus({preventScroll:true});
  }
  expand.addEventListener('click',async()=>{
   if(panel.classList.contains('map-expanded')){await closeExpanded();return;}
   try{if(!panel.requestFullscreen||!document.fullscreenEnabled)throw Error('Use viewport expansion');await panel.requestFullscreen();}
   catch{fallbackExpanded=true;}
   syncExpanded();expand.focus({preventScroll:true});
  });
  document.addEventListener('fullscreenchange',syncExpanded);
  document.addEventListener('keydown',event=>{
   if(!panel.classList.contains('map-expanded'))return;
   if(event.key==='Escape'&&fallbackExpanded){event.preventDefault();closeExpanded();}
   if(event.key==='Tab'){
    const nodes=[...panel.querySelectorAll('button,input,select,a[href],[tabindex="0"]')].filter(n=>!n.disabled&&n.getClientRects().length);
    const first=nodes[0],last=nodes[nodes.length-1];
    if(event.shiftKey&&document.activeElement===first){event.preventDefault();last?.focus();}
    else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first?.focus();}
   }
  });
  if(window.ResizeObserver)new ResizeObserver(()=>map.invalidateSize({pan:true,animate:false})).observe(root);
  const renderer=L.canvas({padding:0.3});const groups=new Map();const entries=[];let loaded=false;let selected=null;
  const query=document.querySelector('#map-search'),filter=document.querySelector('#map-filter-status'),inView=document.querySelector('#map-in-view');
  const requestedStatus=new URLSearchParams(location.search).get('payment_status');if(requestedStatus&&[...filter.options].some(o=>o.value===requestedStatus))filter.value=requestedStatus;
  const palette={unpaid:'#dc2626',partial:'#facc15',paid:'#16a34a',exempt:'#ffffff',unbilled:'#94a3b8'};
  const style=f=>({color:f.properties.payment_status==='exempt'?'#475569':(f.properties.payment_status==='partial'?'#a16207':palette[f.properties.payment_status]||'#64748b'),fillColor:palette[f.properties.payment_status]||'#94a3b8',weight:1.4,fillOpacity:0.68});
  const escape=text=>{const el=document.createElement('span');el.textContent=text;return el.innerHTML;};
  function select(layer){if(selected)selected.setStyle(style(selected.feature));selected=layer;layer.setStyle({color:'#123b59',weight:4,fillOpacity:0.85});layer.bringToFront();}
  function refresh(){
   if(!loaded)return;const term=query.value.trim().toLowerCase(),bounds=map.getBounds();let matched=0;
   for(const entry of entries){const visible=(!term||entry.search.includes(term))&&(!filter.value||entry.feature.properties.payment_status===filter.value)&&(!inView.checked||bounds.intersects(entry.bounds));
    if(visible){matched++;if(!entry.group.hasLayer(entry.layer))entry.group.addLayer(entry.layer);}else if(entry.group.hasLayer(entry.layer))entry.group.removeLayer(entry.layer);
   }
   let shown=0;for(const entry of entries)if(map.hasLayer(entry.group)&&entry.group.hasLayer(entry.layer))shown++;
   status.textContent=`${matched.toLocaleString()} matching records · ${shown.toLocaleString()} on visible layers. Select a parcel to view its details.`;
  }
  function fit(){const bounds=L.latLngBounds([]);for(const entry of entries)if(entry.group.hasLayer(entry.layer)&&map.hasLayer(entry.group))bounds.extend(entry.bounds);if(bounds.isValid())map.fitBounds(bounds,{padding:[35,35],maxZoom:17});}
  fetch(root.dataset.source,{headers:{Accept:'application/json'}}).then(r=>{if(!r.ok)throw Error();return r.json();}).then(data=>{
   if(data.type!=='FeatureCollection'||!Array.isArray(data.features))throw Error();
   for(const feature of data.features){
    const p=feature.properties,name=p.layer_name||'Properties';
    if(!groups.has(name))groups.set(name,{layer:L.featureGroup(),visible:false});const group=groups.get(name);if(p.is_visible_by_default!==0&&p.is_visible_by_default!=='0')group.visible=true;
    const layer=L.geoJSON(feature,{renderer,style:()=>style(feature),pointToLayer:(_f,ll)=>L.circleMarker(ll,{renderer,radius:7,...style(feature)}),onEachFeature:(f,l)=>{
     let popupNode;l.bindPopup(()=>{popupNode??=popupContent(p,l);return popupNode;},{maxWidth:Math.min(330,map.getSize().x-72),minWidth:Math.min(220,map.getSize().x-72),maxHeight:Math.min(420,map.getSize().y-160),autoPanPaddingTopLeft:[12,70],autoPanPaddingBottomRight:[12,12]});l.on('popupclose',()=>{popupNode=null;});l.on('click',()=>select(l));
    }});
    entries.push({feature,layer,group:group.layer,bounds:layer.getBounds(),search:[p.property_number,p.parcel_number,p.land_id,p.locality,p.owners].filter(Boolean).join(' ').toLowerCase()});
   }
   for(const [name,g]of groups){if(g.visible)g.layer.addTo(map);tileConfig.control.addOverlay(g.layer,escape(name));}
   loaded=true;refresh();fit();
  }).catch(()=>{status.textContent='Could not load property locations. Please refresh or use the property register.';});
  let timer;query.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(()=>{refresh();if(!inView.checked)fit();},220);});
  filter.addEventListener('change',refresh);inView.addEventListener('change',refresh);document.querySelector('#map-fit').addEventListener('click',()=>{inView.checked=false;refresh();fit();});
  map.on('moveend',()=>{if(inView.checked)refresh();});map.on('overlayadd overlayremove',refresh);map.on('resize',()=>{const popup=selected?.getPopup();if(popup?.isOpen()){popup.options.maxWidth=Math.min(330,map.getSize().x-72);popup.options.minWidth=Math.min(220,map.getSize().x-72);popup.options.maxHeight=Math.min(420,map.getSize().y-160);popup.update();}});
 }
 const editor=document.querySelector('#boundary-map');if(editor){const map=L.map(editor,{preferCanvas:true}).setView([5.61,-0.13],13);legacyTiles(map,editor);const input=document.querySelector('[name="boundary_geojson"]');let points=[];const draft=L.polyline([],{color:'#b50932'}).addTo(map);try{if(input.value){const existing=L.geoJSON(JSON.parse(input.value)).addTo(map);if(existing.getBounds().isValid())map.fitBounds(existing.getBounds());}}catch{}map.on('click',e=>{points.push(e.latlng);draft.setLatLngs(points);});document.querySelector('#finish-boundary').addEventListener('click',()=>{if(points.length<3){alert('Add at least three corners.');return;}const coordinates=points.map(p=>[Number(p.lng.toFixed(7)),Number(p.lat.toFixed(7))]);coordinates.push(coordinates[0]);input.value=JSON.stringify({type:'Polygon',coordinates:[coordinates]});draft.setLatLngs([...points,points[0]]);});document.querySelector('#clear-boundary').addEventListener('click',()=>{points=[];draft.setLatLngs([]);});}
})();
