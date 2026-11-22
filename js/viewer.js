/* =========================================================
   Ecolrriga — Modelo 3D (Three.js + STLLoader)
   Carrega os 4 arquivos STL do case, monta o conjunto
   (caixa → estantes → arco → tampa) e permite orbitar,
   explodir as peças e ligar/desligar cada parte.
   ========================================================= */

import * as THREE from "three";
import { OrbitControls } from "three/addons/controls/OrbitControls.js";
import { STLLoader } from "three/addons/loaders/STLLoader.js";

const stage = document.getElementById("viewerStage");
const canvas = document.getElementById("modelCanvas");
const loadingEl = document.getElementById("viewerLoading");
const loadingText = document.getElementById("viewerLoadingText");
const dotsEl = document.getElementById("viewerDots");

if (stage && canvas) {
  initViewer();
}

function initViewer() {
  /* ---------------- Cena / Câmera / Render ---------------- */
  const scene = new THREE.Scene();

  const camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
  const HOME_POS = new THREE.Vector3(3.1, 2.3, 4.4);
  camera.position.copy(HOME_POS);

  const renderer = new THREE.WebGLRenderer({
    canvas,
    antialias: true,
    alpha: true,
  });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;

  /* ---------------- Luzes ---------------- */
  scene.add(new THREE.HemisphereLight(0xffffff, 0xb7c9a6, 0.85));

  const keyLight = new THREE.DirectionalLight(0xffffff, 2.1);
  keyLight.position.set(4.5, 7, 3.5);
  keyLight.castShadow = true;
  keyLight.shadow.mapSize.set(1024, 1024);
  keyLight.shadow.camera.near = 0.5;
  keyLight.shadow.camera.far = 30;
  keyLight.shadow.camera.left = -6;
  keyLight.shadow.camera.right = 6;
  keyLight.shadow.camera.top = 6;
  keyLight.shadow.camera.bottom = -6;
  keyLight.shadow.bias = -0.0008;
  scene.add(keyLight);

  const fill = new THREE.DirectionalLight(0xdff0e6, 0.55);
  fill.position.set(-4, 3, -3);
  scene.add(fill);

  /* ---------------- Chão (só captura sombra) ---------------- */
  const ground = new THREE.Mesh(
    new THREE.CircleGeometry(9, 48),
    new THREE.ShadowMaterial({ opacity: 0.16 })
  );
  ground.rotation.x = -Math.PI / 2;
  ground.receiveShadow = true;
  scene.add(ground);

  /* ---------------- Controles ---------------- */
  const controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.075;
  controls.enablePan = false;
  controls.minDistance = 2.6;
  controls.maxDistance = 11;
  controls.maxPolarAngle = Math.PI * 0.52;
  controls.autoRotate = !window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  controls.autoRotateSpeed = 1.3;
  controls.target.set(0, 0, 0);

  /* retoma a rotação automática 2,5s após o usuário soltar */
  let rotateTimer = null;
  controls.addEventListener("start", () => {
    controls.autoRotate = false;
    clearTimeout(rotateTimer);
  });
  controls.addEventListener("end", () => {
    clearTimeout(rotateTimer);
    rotateTimer = setTimeout(() => (controls.autoRotate = true), 2500);
  });

  /* ---------------- Peças do case ---------------- */
  /* Cores retiradas da paleta do design.
     Todas as peças foram exportadas "deitadas"; com rotação de -90° no X:
     - caixa fica EM PÉ (altura = eixo Z original), entrada virada pra frente;
     - arco vira o quadro da porta em pé, na frente da entrada;
     - tampa vira a porta deslizante que fecha a entrada, encaixada
       no canal do topo do arco;
     - estantes vira prateleira horizontal dentro da caixa. */
  const PARTS = [
    { key: "caixa",    file: "models/Caixa Case SE.stl",    color: 0x004e3e, pos: "base" },
    { key: "estantes", file: "models/Estantes Case SE.stl", color: 0x818c46, pos: "shelf" },
    { key: "arco",     file: "models/Arco Case SE.stl",     color: 0x9eb2a4, pos: "front" },
    { key: "tampa",    file: "models/Tampa Case SE.stl",    color: 0xe9a12e, pos: "lid" },
  ];
  const ROT_X = -Math.PI / 2;   // para todas as peças: -90° em volta de X

  const group = new THREE.Group();
  scene.add(group);

  const meshes = {};      // key -> mesh
  const metrics = {};     // key -> { sx, sy, sz } (dimensões originais do STL)
  let explode = 0;        // 0 = montado, 1 = totalmente explodido

  const manager = new THREE.LoadingManager();
  manager.onProgress = (_url, done, total) => {
    loadingText.textContent = `Carregando modelo 3D… (${done}/${total})`;
  };
  manager.onLoad = () => {
    buildAssembly();
    loadingEl.classList.add("hidden");
  };
  manager.onError = (url) => {
    console.error("Falha ao carregar:", url);
    loadingText.innerHTML =
      "Não foi possível carregar os STLs.<br>Serva a pasta com um servidor local (ex.: <code>python -m http.server</code>).";
  };

  const loader = new STLLoader(manager);

  PARTS.forEach((part) => {
    loader.load(
      encodeURI(part.file),
      (geometry) => {
        geometry.computeBoundingBox();
        const box = geometry.boundingBox;
        const size = new THREE.Vector3();
        const center = new THREE.Vector3();
        box.getSize(size);
        box.getCenter(center);

        /* centraliza a peça na origem */
        geometry.translate(-center.x, -center.y, -center.z);

        const material = new THREE.MeshStandardMaterial({
          color: part.color,
          roughness: 0.62,
          metalness: 0.1,
        });
        const mesh = new THREE.Mesh(geometry, material);
        mesh.castShadow = true;
        mesh.receiveShadow = true;
        mesh.name = part.key;
        mesh.userData.pos = part.pos;
        mesh.rotation.x = ROT_X;            // endireita a peça

        group.add(mesh);
        meshes[part.key] = mesh;
        metrics[part.key] = { sx: size.x, sy: size.y, sz: size.z };
      },
      undefined,
      () => manager.onError(part.file)
    );
  });

  /* ---------------- Montagem / explodição ----------------
     Após a rotação de -90° no X (eixo Y = altura):
       caixa   : 20 (larg) x 24 (alt) x 20 (prof) — entrada virada p/ +Z (frente)
       arco    : 28 x 26 x 3  — quadro da porta em pé, à frente da entrada
       tampa   : 18 x 30 x 1  — porta deslizante encaixada no canal do topo do arco,
                                fechando a entrada da caixa
       estantes: 16 x 0.3 x 15 — prateleira horizontal dentro da caixa        */
  function buildAssembly() {
    if (!metrics.caixa) return;

    const mC = metrics.caixa;
    const ch = mC.sz;            // altura da caixa em pé (eixo Z original = 24)
    const cd = mC.sy;            // profundidade (eixo Y original = 20)
    const ah = metrics.arco.sz;  // altura do arco (26)
    const ad = metrics.arco.sy;  // espessura do arco (3)
    const th = metrics.tampa.sz; // altura da tampa (30)

    const zC = cd / 2;                        // face da frente da caixa
    const zArco = zC + ad / 2 + 0.6;          // plano do arco
    const place = {
      base:    { x: 0, y: ch / 2,                z: 0 },
      shelf:   { x: 0, y: ch * 0.42 + explode * ch * 1.6, z: 0 },
      front:   { x: 0, y: ah / 2,                z: zArco + explode * ah * 1.15 },
      lid:     { x: -0.125, y: th / 2 + explode * ch * 0.5, z: zArco - 0.11 },
    };

    Object.entries(meshes).forEach(([key, mesh]) => {
      const p = place[mesh.userData.pos];
      if (p) mesh.position.set(p.x, p.y, p.z);
    });

    fitAssembly();
  }

  function fitAssembly() {
    if (!group.children.length) return;

    /* zera a transformação para medir o conjunto em espaço local */
    group.scale.setScalar(1);
    group.position.y = 0;

    const box = new THREE.Box3().setFromObject(group);
    const size = new THREE.Vector3();
    const center = new THREE.Vector3();
    box.getSize(size);
    box.getCenter(center);

    /* escala para o conjunto caber na vista */
    const fitH = 2.15 / Math.max(size.y, 0.001);
    const fitW = 2.6 / Math.max(size.x, size.z, 0.001);
    const s = Math.min(fitH, fitW, 0.09);

    group.scale.setScalar(s);
    group.position.y = -center.y * s;      /* centraliza na origem */

    /* chão encostando na base do modelo (para a sombra) */
    ground.position.y = (box.min.y - center.y) * s + 0.003;
  }

  /* ---------------- UI: botões, chips, explodição ---------------- */
  document.getElementById("btnReset")?.addEventListener("click", () => {
    camera.position.copy(HOME_POS);
    controls.target.set(0, 0, 0);
    controls.autoRotate = true;
    controls.update();
  });

  document.getElementById("btnFullscreen")?.addEventListener("click", () => {
    if (document.fullscreenElement) {
      document.exitFullscreen();
    } else {
      stage.requestFullscreen?.();
    }
  });

  const explodeRange = document.getElementById("explodeRange");
  explodeRange?.addEventListener("input", (e) => {
    explode = Number(e.target.value) / 100;
    buildAssembly();
  });

  document.getElementById("partChips")?.addEventListener("click", (e) => {
    const chip = e.target.closest(".chip");
    if (!chip) return;
    const key = chip.dataset.part;
    const mesh = meshes[key];
    if (!mesh) return;
    mesh.visible = !mesh.visible;
    chip.classList.toggle("active", mesh.visible);
    fitAssembly();
  });

  /* ---------------- Dots acompanham a rotação ---------------- */
  const dots = dotsEl ? [...dotsEl.children] : [];
  function updateDots() {
    if (dots.length < 4) return;
    const azimuth = controls.getAzimuthalAngle();
    const norm = ((azimuth % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);
    const idx = Math.floor(norm / (Math.PI / 2)) % 4;
    dots.forEach((d, i) => d.classList.toggle("on", i === idx));
  }

  /* ---------------- Resize ---------------- */
  function resize() {
    const w = stage.clientWidth;
    const h = stage.clientHeight;
    if (!w || !h) return;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h, false);
  }
  new ResizeObserver(resize).observe(stage);
  resize();
  document.addEventListener("fullscreenchange", () => setTimeout(resize, 60));

  /* ---------------- Loop ---------------- */
  let visible = true;
  new IntersectionObserver(
    ([entry]) => (visible = entry.isIntersecting),
    { threshold: 0.05 }
  ).observe(stage);

  renderer.setAnimationLoop(() => {
    if (!visible) return;
    controls.update();
    updateDots();
    renderer.render(scene, camera);
  });
}
