<template>
  <canvas ref="networkCanvas" class="network-canvas"></canvas>
  <div class="glow-orb glow-orb-1"></div>
  <div class="glow-orb glow-orb-2"></div>
  <div class="sparkle-field">
    <span class="sparkle s1"></span>
    <span class="sparkle s2"></span>
    <span class="sparkle s3"></span>
    <span class="sparkle s4"></span>
    <span class="sparkle s5"></span>
    <span class="sparkle s6"></span>
    <span class="sparkle s7"></span>
    <span class="sparkle s8"></span>
    <span class="sparkle s9"></span>
    <span class="sparkle s10"></span>
    <span class="sparkle s11"></span>
    <span class="sparkle s12"></span>
    <span class="sparkle s13"></span>
    <span class="sparkle s14"></span>
    <span class="sparkle s15"></span>
    <span class="sparkle s16"></span>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const networkCanvas = ref(null)
let ctx = null
let particles = []
let rafId = null

function initParticles () {
  const canvas = networkCanvas.value
  if (!canvas) return
  const count = Math.min(120, Math.max(45, Math.floor((canvas.width * canvas.height) / 11000)))
  particles = Array.from({ length: count }, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    vx: (Math.random() - 0.5) * 0.32,
    vy: (Math.random() - 0.5) * 0.32,
    r: Math.random() * 1.8 + 1.2
  }))
}

function resizeCanvas () {
  const canvas = networkCanvas.value
  if (!canvas) return
  canvas.width = canvas.parentElement.clientWidth
  canvas.height = canvas.parentElement.clientHeight
  initParticles()
}

function tick () {
  const canvas = networkCanvas.value
  if (!canvas || !ctx) return
  const { width, height } = canvas
  ctx.clearRect(0, 0, width, height)

  for (const p of particles) {
    p.x += p.vx
    p.y += p.vy
    if (p.x <= 0 || p.x >= width) p.vx *= -1
    if (p.y <= 0 || p.y >= height) p.vy *= -1
  }

  const maxDist = 150
  for (let i = 0; i < particles.length; i++) {
    for (let j = i + 1; j < particles.length; j++) {
      const a = particles[i]
      const b = particles[j]
      const dx = a.x - b.x
      const dy = a.y - b.y
      const dist = Math.sqrt(dx * dx + dy * dy)
      if (dist < maxDist) {
        const alpha = (1 - dist / maxDist) * 0.5
        ctx.strokeStyle = `rgba(230, 178, 96, ${alpha})`
        ctx.lineWidth = 1
        ctx.beginPath()
        ctx.moveTo(a.x, a.y)
        ctx.lineTo(b.x, b.y)
        ctx.stroke()
      }
    }
  }

  for (const p of particles) {
    ctx.beginPath()
    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2)
    ctx.fillStyle = 'rgba(245, 208, 140, 0.95)'
    ctx.shadowColor = 'rgba(240, 200, 120, 0.95)'
    ctx.shadowBlur = 9
    ctx.fill()
    ctx.shadowBlur = 0
  }

  rafId = requestAnimationFrame(tick)
}

onMounted(() => {
  const canvas = networkCanvas.value
  ctx = canvas.getContext('2d')
  resizeCanvas()
  tick()
  window.addEventListener('resize', resizeCanvas)
})

onBeforeUnmount(() => {
  if (rafId) cancelAnimationFrame(rafId)
  window.removeEventListener('resize', resizeCanvas)
})
</script>

<style scoped>
.network-canvas {
  position: absolute;
  inset: 0;
  z-index: 0;
  width: 100%;
  height: 100%;
}

.sparkle-field {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}
.sparkle {
  position: absolute;
  width: 12px;
  height: 12px;
  background: radial-gradient(circle, #fffaf0 0%, #f0c878 45%, transparent 75%);
  clip-path: polygon(50% 0%, 61% 40%, 100% 50%, 61% 60%, 50% 100%, 39% 60%, 0% 50%, 39% 40%);
  filter: drop-shadow(0 0 6px rgba(255, 220, 150, 0.85));
  opacity: 0;
  animation-name: twinkleMountain;
  animation-timing-function: ease-in-out;
  animation-iteration-count: infinite;
}
@keyframes twinkleMountain {
  0%, 100% { opacity: 0; transform: scale(.25) rotate(0deg); }
  50% { opacity: 1; transform: scale(1.2) rotate(10deg); }
}
.s1  { top: 12%;  left: 15%; width: 9px;  height: 9px;  animation-duration: 4.5s; animation-delay: 0s; }
.s2  { top: 22%;  left: 78%; width: 14px; height: 14px; animation-duration: 5.5s; animation-delay: 1.2s; }
.s3  { top: 8%;   left: 50%; width: 8px;  height: 8px;  animation-duration: 3.8s; animation-delay: 2.1s; }
.s4  { top: 35%;  left: 88%; width: 10px; height: 10px; animation-duration: 6s;   animation-delay: .6s; }
.s5  { top: 60%;  left: 6%;  width: 11px; height: 11px; animation-duration: 4.2s; animation-delay: 3s; }
.s6  { top: 78%;  left: 20%; width: 9px;  height: 9px;  animation-duration: 5s;   animation-delay: 1.8s; }
.s7  { top: 15%;  left: 32%; width: 13px; height: 13px; animation-duration: 4.8s; animation-delay: 2.6s; }
.s8  { top: 70%;  left: 85%; width: 8px;  height: 8px;  animation-duration: 3.6s; animation-delay: .3s; }
.s9  { top: 88%;  left: 55%; width: 12px; height: 12px; animation-duration: 5.2s; animation-delay: 3.6s; }
.s10 { top: 45%;  left: 4%;  width: 10px; height: 10px; animation-duration: 4.4s; animation-delay: 4.2s; }
.s11 { top: 5%;   left: 68%; width: 11px; height: 11px; animation-duration: 4.9s; animation-delay: 5s; }
.s12 { top: 52%;  left: 92%; width: 9px;  height: 9px;  animation-duration: 4s;   animation-delay: 2.4s; }
.s13 { top: 30%;  left: 10%; width: 12px; height: 12px; animation-duration: 5.3s; animation-delay: 1s; }
.s14 { top: 65%;  left: 45%; width: 8px;  height: 8px;  animation-duration: 3.9s; animation-delay: 4.8s; }
.s15 { top: 92%;  left: 15%; width: 10px; height: 10px; animation-duration: 5.6s; animation-delay: 2s; }
.s16 { top: 18%;  left: 95%; width: 9px;  height: 9px;  animation-duration: 4.3s; animation-delay: 3.3s; }

.glow-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  z-index: 0;
  animation: floatOrb 16s ease-in-out infinite alternate;
}
.glow-orb-1 {
  width: 380px; height: 380px;
  top: -100px; left: -100px;
  background: rgba(214, 158, 46, 0.16);
}
.glow-orb-2 {
  width: 320px; height: 320px;
  bottom: -110px; right: -70px;
  background: rgba(160, 100, 40, 0.18);
  animation-delay: -7s;
}
@keyframes floatOrb {
  from { transform: translate(0, 0) scale(1); }
  to { transform: translate(35px, -25px) scale(1.15); }
}
</style>
