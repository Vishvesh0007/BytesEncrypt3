// React Bits <GradientText /> Vanilla JS engine
(function () {
  const VARIANTS = ['linear', 'flow', 'conic'];
  const ANGLES = { horizontal: 90, vertical: 180, diagonal: 135 };
  const TAU = Math.PI * 2;
  const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

  function initGradientText(el, options = {}) {
    if (!el) return;
    const colors = options.colors || ['#D4FF24', '#70FF00', '#A3FF2E', '#F4FF77'];
    const palette = colors.length > 1 ? colors : [colors[0] || '#ffffff', colors[0] || '#ffffff'];
    const animationSpeed = options.animationSpeed || 6;
    const variant = VARIANTS.includes(options.variant) ? options.variant : 'flow';
    const angle = options.direction ? (ANGLES[options.direction] || options.angle || 90) : (options.angle || 90);
    const scale = clamp(options.scale || 3, 1, 6);
    const yoyo = options.yoyo !== undefined ? options.yoyo : true;
    const pauseOnHover = options.pauseOnHover || false;
    const followPointer = options.followPointer || false;
    const glow = clamp(options.glow !== undefined ? options.glow : 0.35, 0, 1);

    el.style.setProperty('--gt-border', `${options.borderWidth || 1.5}px`);
    el.style.setProperty('--gt-glow', glow);

    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches || false;
    const state = { time: 0, inside: false, visible: true, x: 0.5, y: 0.5, px: 0.5, py: 0.5, vx: 0, vy: 0, pull: 0 };
    let raf = 0;
    let last = 0;
    let alive = true;

    const paint = () => {
      const list = palette;
      const cycle = state.time / animationSpeed;
      let image;
      if (variant === 'flow') {
        const blobs = list.map((color, i) => {
          const spin = cycle * TAU;
          const rate = 0.55 + ((i * 0.618) % 1) * 0.5;
          let x = 50 + 44 * Math.sin(spin * rate + i * 2.1);
          let y = 50 + 40 * Math.sin(spin * (rate * 0.83 + 0.21) + i * 1.3);
          if (i === 0) {
            x += (state.px * 100 - x) * state.pull;
            y += (state.py * 100 - y) * state.pull;
          }
          return `radial-gradient(ellipse ${(22 + 12 * scale).toFixed(1)}% ${(60 + 30 * scale).toFixed(1)}% at ${x.toFixed(2)}% ${y.toFixed(2)}%, ${color} 0%, transparent 100%)`;
        });
        image = `${blobs.join(', ')}, linear-gradient(${angle}deg, ${list.join(', ')})`;
      } else if (variant === 'conic') {
        const sway = yoyo ? Math.sin(cycle * Math.PI) * 50 : cycle * 360;
        const cx = 50 + (state.px * 100 - 50) * state.pull;
        const cy = 260 + (state.py * 100 - 260) * state.pull;
        image = `repeating-conic-gradient(from ${(sway - 30).toFixed(2)}deg at ${cx.toFixed(2)}% ${cy.toFixed(2)}%, ${[...list, list[0]].map((color, i) => `${color} ${((i / list.length) * 60 * (3 / scale)).toFixed(2)}deg`).join(', ')})`;
      } else {
        const loop = [...list, list[0]];
        const period = 100 * scale;
        let offset;
        if (yoyo) {
          const swing = cycle % 2;
          const t = swing < 1 ? swing : 2 - swing;
          offset = (0.5 - Math.cos(Math.PI * t) / 2) * (period - 100);
        } else {
          offset = (cycle % 1) * period;
        }
        offset += (state.px - 0.5) * period * 0.5 * state.pull;
        const stops = loop.map((color, i) => `${color} ${((i / (loop.length - 1)) * period - offset).toFixed(2)}%`);
        image = `repeating-linear-gradient(${angle}deg, ${stops.join(', ')})`;
      }
      el.style.setProperty('--gt-gradient', image);
    };

    const tick = now => {
      raf = 0;
      if (!alive) return;
      const dt = Math.min(0.05, Math.max(0, (now - last) / 1000));
      last = now;
      const paused = reduce || (pauseOnHover && state.inside);
      if (!paused) state.time += dt;
      const target = followPointer && state.inside ? 1 : 0;
      state.pull += (target - state.pull) * (1 - Math.exp(-dt * 6));
      const stiffness = 160;
      state.vx += ((state.x - state.px) * stiffness - state.vx * 2 * Math.sqrt(stiffness)) * dt;
      state.vy += ((state.y - state.py) * stiffness - state.vy * 2 * Math.sqrt(stiffness)) * dt;
      state.px += state.vx * dt;
      state.py += state.vy * dt;
      paint();
      const settling = Math.abs(state.pull - target) > 0.001 || Math.abs(state.vx) + Math.abs(state.vy) > 0.001;
      if (!state.visible || (paused && !settling)) return;
      raf = requestAnimationFrame(tick);
    };

    const wake = () => {
      if (raf || !alive) return;
      last = performance.now();
      raf = requestAnimationFrame(tick);
    };

    const locate = event => {
      const rect = el.getBoundingClientRect();
      state.x = clamp((event.clientX - rect.left) / rect.width, 0, 1);
      state.y = clamp((event.clientY - rect.top) / rect.height, 0, 1);
    };
    const onEnter = event => {
      locate(event);
      if (!state.inside) {
        state.px = state.x;
        state.py = state.y;
      }
      state.inside = true;
      wake();
    };
    const onMove = event => {
      locate(event);
      wake();
    };
    const onLeave = () => {
      state.inside = false;
      wake();
    };

    const observer = new IntersectionObserver(entries => {
      state.visible = entries.some(entry => entry.isIntersecting);
      if (state.visible) wake();
    });
    observer.observe(el);
    el.addEventListener('pointerenter', onEnter);
    el.addEventListener('pointermove', onMove);
    el.addEventListener('pointerleave', onLeave);
    paint();
    wake();
  }

  window.initGradientText = initGradientText;

  document.addEventListener('DOMContentLoaded', function () {
    const heroGradientEl = document.getElementById('heroGradientText');
    if (heroGradientEl) {
      initGradientText(heroGradientEl, {
        colors: ['#D4FF24', '#70FF00', '#A3FF2E', '#E6FF66', '#B0FF1C'],
        animationSpeed: 6,
        variant: 'flow',
        glow: 0.35,
        scale: 3,
        followPointer: true
      });
    }
  });
})();

