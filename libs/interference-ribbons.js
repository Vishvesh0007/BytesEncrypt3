/**
 * Interference Ribbons Background Component
 * A ribbon of fine glowing wires drifting and twisting with an acid lime / green gradient.
 * High performance WebGL shader implementation for HTML / Vanilla JS / React.
 */
(function (global) {
  'use strict';

  var VERT_SHADER = [
    'attribute vec2 a_pos;',
    'void main() {',
    '  gl_Position = vec4(a_pos, 0.0, 1.0);',
    '}'
  ].join('\n');

  var FRAG_SHADER = [
    'precision highp float;',
    'uniform vec2 u_res;',
    'uniform float u_time;',
    'uniform vec3 u_colorA;',  // Primary electric royal blue (e.g. #2563EB / #1E40AF)
    'uniform vec3 u_colorB;',  // Vivid cyan / sky blue highlight (#38BDF8 / #60A5FA)
    'uniform vec3 u_colorC;',  // Deep navy core (#0B1530 / #0756D4)
    '',
    '// 2D Rotation matrix',
    'mat2 rot(float a) {',
    '  float s = sin(a), c = cos(a);',
    '  return mat2(c, -s, s, c);',
    '}',
    '',
    'void main() {',
    '  vec2 uv = (gl_FragCoord.xy - 0.5 * u_res) / min(u_res.x, u_res.y);',
    '  float t = u_time * 0.45;',
    '',
    '  // Initial drift & rotation angle',
    '  vec2 p = uv;',
    '  p = rot(sin(t * 0.2) * 0.45 + 0.3) * p;',
    '',
    '  // Twist and displacement across multiple ribbon layers',
    '  float ribbon = 0.0;',
    '  float fineWires = 0.0;',
    '',
    '  for (float i = 0.0; i < 7.0; i += 1.0) {',
    '    float offset = i * 0.18 + t * 0.12;',
    '    float wave = sin(p.x * 2.8 + offset * 2.2 + sin(p.y * 3.5 + t * 0.5) * 1.4);',
    '    float wave2 = cos(p.y * 3.1 - offset * 1.7 + cos(p.x * 2.4 - t * 0.3) * 1.1);',
    '    ',
    '    // Wire distance coordinate',
    '    float d = abs(p.y - (wave * 0.35 + wave2 * 0.18));',
    '    ',
    '    // Soft glowing ribbon core',
    '    ribbon += (0.012 / (d + 0.045)) * 0.25;',
    '    ',
    '    // Fine sharp interference filaments',
    '    float wireFreq = sin((p.x + p.y + d * 18.0) * 45.0 + t * 3.0);',
    '    fineWires += (0.0035 / (d * 1.2 + 0.009)) * (0.55 + 0.45 * wireFreq);',
    '  }',
    '',
    '  // Blue gradient mixing matching the hero section background',
    '  float gradMix = clamp(p.x * 0.6 + sin(t * 0.5) * 0.3 + 0.5, 0.0, 1.0);',
    '  vec3 wireColor = mix(u_colorA, u_colorB, gradMix);',
    '  vec3 finalColor = u_colorC * ribbon * 0.7 + wireColor * (fineWires * 0.95 + ribbon * 0.75);',
    '',
    '  // Soft vignette so edges fade smoothly',
    '  vec2 normCoord = gl_FragCoord.xy / u_res;',
    '  float edgeFade = smoothstep(0.0, 0.12, normCoord.x) * smoothstep(1.0, 0.88, normCoord.x) *',
    '                  smoothstep(0.0, 0.15, normCoord.y) * smoothstep(1.0, 0.85, normCoord.y);',
    '',
    '  // Blend onto dark cybersecurity navy background (#050711)',
    '  vec3 bg = vec3(0.02, 0.03, 0.07);',
    '  vec3 col = bg + finalColor * edgeFade;',
    '',
    '  gl_FragColor = vec4(col, 1.0);',
    '}'
  ].join('\n');

  function initInterferenceRibbons(container, options) {
    if (!container) return;
    options = options || {};

    var canvas = document.createElement('canvas');
    canvas.className = 'interference-ribbons-canvas';
    canvas.style.position = 'absolute';
    canvas.style.inset = '0';
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.pointerEvents = 'none';
    canvas.style.zIndex = '0';
    canvas.style.opacity = options.opacity !== undefined ? options.opacity : '1';
    
    // Ensure container is positioned
    var computedStyle = window.getComputedStyle(container);
    if (computedStyle.position === 'static') {
      container.style.position = 'relative';
    }
    container.style.overflow = 'hidden';
    container.insertBefore(canvas, container.firstChild);

    var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
    if (!gl) {
      // Fallback CSS gradient if WebGL is unavailable
      canvas.style.background = 'radial-gradient(ellipse at 50% 50%, rgba(212, 255, 36, 0.18) 0%, rgba(9, 44, 28, 0.85) 60%, #06140D 100%)';
      return { destroy: function () { if (canvas.parentNode) canvas.parentNode.removeChild(canvas); } };
    }

    function createShader(type, src) {
      var s = gl.createShader(type);
      gl.shaderSource(s, src);
      gl.compileShader(s);
      if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
        console.error(gl.getShaderInfoLog(s));
        gl.deleteShader(s);
        return null;
      }
      return s;
    }

    var vs = createShader(gl.VERTEX_SHADER, VERT_SHADER);
    var fs = createShader(gl.FRAGMENT_SHADER, FRAG_SHADER);
    if (!vs || !fs) return;

    var prog = gl.createProgram();
    gl.attachShader(prog, vs);
    gl.attachShader(prog, fs);
    gl.linkProgram(prog);
    if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) {
      console.error(gl.getProgramInfoLog(prog));
      return;
    }

    var uRes = gl.getUniformLocation(prog, 'u_res');
    var uTime = gl.getUniformLocation(prog, 'u_time');
    var uColA = gl.getUniformLocation(prog, 'u_colorA');
    var uColB = gl.getUniformLocation(prog, 'u_colorB');
    var uColC = gl.getUniformLocation(prog, 'u_colorC');

    var buf = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buf);
    // Fullscreen quad
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([
      -1, -1,
       1, -1,
      -1,  1,
      -1,  1,
       1, -1,
       1,  1
    ]), gl.STATIC_DRAW);

    var aPos = gl.getAttribLocation(prog, 'a_pos');
    gl.enableVertexAttribArray(aPos);
    gl.vertexAttribPointer(aPos, 2, gl.FLOAT, false, 0, 0);

    var width = 0;
    var height = 0;
    var animFrame = null;
    var isRunning = true;
    var startTime = performance.now();

    function resize() {
      var dpr = Math.min(window.devicePixelRatio || 1, 2);
      var w = container.clientWidth;
      var h = container.clientHeight;
      if (w === 0 || h === 0) return;
      if (canvas.width !== Math.floor(w * dpr) || canvas.height !== Math.floor(h * dpr)) {
        canvas.width = Math.floor(w * dpr);
        canvas.height = Math.floor(h * dpr);
        width = canvas.width;
        height = canvas.height;
        gl.viewport(0, 0, width, height);
      }
    }

    window.addEventListener('resize', resize);
    resize();

    // Default blue gradient palette matching Hero section background:
    // Color A: Electric royal blue #1E40AF -> rgb(0.12, 0.25, 0.82)
    // Color B: Sky cyan highlight #38BDF8 -> rgb(0.22, 0.74, 0.97)
    // Color C: Deep navy ambient #0B1530 -> rgb(0.04, 0.08, 0.25)
    var colA = options.colorA || [0.12, 0.32, 0.90];
    var colB = options.colorB || [0.22, 0.74, 0.97];
    var colC = options.colorC || [0.04, 0.08, 0.28];

    function render(time) {
      if (!isRunning) return;
      resize();

      if (width > 0 && height > 0) {
        gl.useProgram(prog);
        gl.uniform2f(uRes, width, height);
        gl.uniform1f(uTime, (time - startTime) * 0.001);
        gl.uniform3f(uColA, colA[0], colA[1], colA[2]);
        gl.uniform3f(uColB, colB[0], colB[1], colB[2]);
        gl.uniform3f(uColC, colC[0], colC[1], colC[2]);

        gl.drawArrays(gl.TRIANGLES, 0, 6);
      }

      animFrame = requestAnimationFrame(render);
    }

    animFrame = requestAnimationFrame(render);

    // Pause when offscreen
    if ('IntersectionObserver' in window) {
      var observer = new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) {
          if (!isRunning) {
            isRunning = true;
            startTime = performance.now();
            animFrame = requestAnimationFrame(render);
          }
        } else {
          isRunning = false;
          if (animFrame) cancelAnimationFrame(animFrame);
        }
      }, { threshold: 0.05 });
      observer.observe(container);
    }

    return {
      destroy: function () {
        isRunning = false;
        if (animFrame) cancelAnimationFrame(animFrame);
        window.removeEventListener('resize', resize);
        if (canvas.parentNode) canvas.parentNode.removeChild(canvas);
      }
    };
  }

  global.initInterferenceRibbons = initInterferenceRibbons;

  // Auto-init for data attributes
  document.addEventListener('DOMContentLoaded', function () {
    var ctaEls = document.querySelectorAll('[data-interference-ribbons]');
    ctaEls.forEach(function (el) {
      initInterferenceRibbons(el);
    });
  });
})(typeof window !== 'undefined' ? window : this);

