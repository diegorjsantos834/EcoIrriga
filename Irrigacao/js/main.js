/* =========================================================
   Ecolrriga — UI: navbar, menu mobile, reveal, gráfico
   ========================================================= */

/* ---------------- Navbar ---------------- */
const navbar = document.getElementById("navbar");
const navToggle = document.getElementById("navToggle");
const navLinks = document.getElementById("navLinks");

window.addEventListener("scroll", () => {
  navbar.classList.toggle("scrolled", window.scrollY > 8);
}, { passive: true });

navToggle.addEventListener("click", () => {
  const open = navLinks.classList.toggle("open");
  navToggle.classList.toggle("open", open);
  navToggle.setAttribute("aria-expanded", String(open));
});

navLinks.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", () => {
    navLinks.classList.remove("open");
    navToggle.classList.remove("open");
    navToggle.setAttribute("aria-expanded", "false");
  });
});

/* ---------------- Link ativo conforme scroll ---------------- */
const sections = [...document.querySelectorAll("main section[id], footer[id]")];
const menuLinks = [...navLinks.querySelectorAll(".nav-link")];

const spy = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      const id = entry.target.id;
      menuLinks.forEach((l) => {
        l.classList.toggle("active", l.getAttribute("href") === `#${id}`);
      });
    });
  },
  { rootMargin: "-45% 0px -50% 0px" }
);
sections.forEach((s) => spy.observe(s));

/* ---------------- Animação de entrada ---------------- */
const revealObserver = new IntersectionObserver(
  (entries, obs) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("visible");
        obs.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.15 }
);
document.querySelectorAll(".reveal").forEach((el) => revealObserver.observe(el));

/* ---------------- Gráfico de previsão ---------------- */
function drawForecastChart(progress = 1) {
  const canvas = document.getElementById("forecastChart");
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  const W = canvas.width;
  const H = canvas.height;

  const pad = { top: 26, right: 26, bottom: 42, left: 48 };
  const plotW = W - pad.left - pad.right;
  const plotH = H - pad.top - pad.bottom;

  const labels = ["01/11", "02/11", "03/11", "04/11", "05/11"];
  const values = [85, 82, 85, 58, 40];

  ctx.clearRect(0, 0, W, H);

  /* grade + eixo Y */
  ctx.font = "500 12px Inter, sans-serif";
  ctx.textAlign = "right";
  ctx.textBaseline = "middle";
  [0, 50, 100].forEach((tick) => {
    const y = pad.top + plotH - (tick / 100) * plotH;
    ctx.strokeStyle = "rgba(19, 36, 11, .10)";
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(pad.left, y);
    ctx.lineTo(W - pad.right, y);
    ctx.stroke();

    ctx.fillStyle = "#7A8474";
    ctx.fillText(`${tick}%`, pad.left - 10, y);
  });

  const pts = values.map((v, i) => ({
    x: pad.left + (i / (values.length - 1)) * plotW,
    y: pad.top + plotH - (v / 100) * plotH,
  }));

  /* área preenchida (até o progresso) */
  const cut = pad.left + plotW * progress;
  ctx.save();
  ctx.beginPath();
  ctx.rect(pad.left, 0, plotW * progress, H);
  ctx.clip();

  const grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + plotH);
  grad.addColorStop(0, "rgba(189, 217, 169, .75)");
  grad.addColorStop(1, "rgba(189, 217, 169, .05)");

  ctx.beginPath();
  ctx.moveTo(pts[0].x, pad.top + plotH);
  pts.forEach((p) => ctx.lineTo(p.x, p.y));
  ctx.lineTo(pts[pts.length - 1].x, pad.top + plotH);
  ctx.closePath();
  ctx.fillStyle = grad;
  ctx.fill();

  /* linha */
  ctx.beginPath();
  ctx.moveTo(pts[0].x, pts[0].y);
  for (let i = 1; i < pts.length; i++) {
    const p0 = pts[i - 1];
    const p1 = pts[i];
    const cx = (p0.x + p1.x) / 2;
    ctx.bezierCurveTo(cx, p0.y, cx, p1.y, p1.x, p1.y);
  }
  ctx.strokeStyle = "#818C46";
  ctx.lineWidth = 3;
  ctx.lineJoin = "round";
  ctx.stroke();
  ctx.restore();

  /* pontos */
  pts.forEach((p, i) => {
    if (p.x > cut) return;
    ctx.beginPath();
    ctx.arc(p.x, p.y, 6, 0, Math.PI * 2);
    ctx.fillStyle = i === 0 || i === 2 ? "#E9A12E" : "#004E3E";
    ctx.fill();
    ctx.lineWidth = 3;
    ctx.strokeStyle = "#fff";
    ctx.stroke();
  });

  /* eixo X */
  ctx.font = "500 12px Inter, sans-serif";
  ctx.textAlign = "center";
  ctx.textBaseline = "top";
  ctx.fillStyle = "#7A8474";
  labels.forEach((label, i) => {
    const x = pad.left + (i / (labels.length - 1)) * plotW;
    ctx.fillText(label, x, pad.top + plotH + 14);
  });
}

/* Anima o traçado quando o gráfico entra na tela */
const chartCanvas = document.getElementById("forecastChart");
if (chartCanvas) {
  drawForecastChart(0);
  let started = false;
  const chartObs = new IntersectionObserver(
    (entries, obs) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting || started) return;
        started = true;
        const t0 = performance.now();
        const dur = 1400;
        const step = (now) => {
          const t = Math.min((now - t0) / dur, 1);
          const eased = 1 - Math.pow(1 - t, 3);
          drawForecastChart(eased);
          if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
        obs.disconnect();
      });
    },
    { threshold: 0.35 }
  );
  chartObs.observe(chartCanvas);
}

/* ---------------- Carrossel do projeto ---------------- */
(() => {
  const root = document.getElementById("projectCarousel");
  if (!root) return;

  const track = root.querySelector(".carousel-track");
  const slides = [...root.querySelectorAll(".carousel-slide")];
  const dotsBox = root.querySelector(".carousel-dots");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  let index = 0;
  let timer = null;

  /* dots */
  const dots = slides.map((_, i) => {
    const b = document.createElement("button");
    b.type = "button";
    b.setAttribute("role", "tab");
    b.setAttribute("aria-label", `Ir para o slide ${i + 1}`);
    b.addEventListener("click", () => go(i, true));
    dotsBox.appendChild(b);
    return b;
  });

  function render() {
    track.style.transform = `translateX(-${index * 100}%)`;
    dots.forEach((d, i) => {
      d.classList.toggle("on", i === index);
      d.setAttribute("aria-selected", String(i === index));
    });
    slides.forEach((s, i) => s.setAttribute("aria-hidden", String(i !== index)));
  }

  function go(i, user) {
    index = (i + slides.length) % slides.length;
    render();
    if (user) restart();
  }

  /* setas */
  root.querySelectorAll(".carousel-btn").forEach((btn) => {
    btn.addEventListener("click", () => go(index + Number(btn.dataset.dir), true));
  });

  /* autoplay (para no hover/foco e com reduced motion) */
  function restart() {
    stop();
    if (reduceMotion) return;
    timer = setInterval(() => go(index + 1), 7000);
  }
  function stop() {
    if (timer) clearInterval(timer);
    timer = null;
  }
  root.addEventListener("mouseenter", stop);
  root.addEventListener("mouseleave", restart);
  root.addEventListener("focusin", stop);
  root.addEventListener("focusout", restart);

  /* teclado */
  root.addEventListener("keydown", (e) => {
    if (e.key === "ArrowLeft") { e.preventDefault(); go(index - 1, true); }
    if (e.key === "ArrowRight") { e.preventDefault(); go(index + 1, true); }
  });

  /* swipe (pointer) — ignorado quando o alvo é o esquema, que rola na horizontal */
  let startX = 0, dragging = false;
  root.addEventListener("pointerdown", (e) => {
    if (e.target.closest(".scheme")) return;   // deixa rolar o SVG normalmente
    if (e.target.closest("button")) return;
    dragging = true;
    startX = e.clientX;
  });
  root.addEventListener("pointerup", (e) => {
    if (!dragging) return;
    dragging = false;
    const dx = e.clientX - startX;
    if (Math.abs(dx) > 50) go(index + (dx < 0 ? 1 : -1), true);
  });
  root.addEventListener("pointercancel", () => (dragging = false));

  render();
  restart();
})();
