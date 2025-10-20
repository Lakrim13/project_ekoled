# 🎨 EKOLED VISUAL DESIGN GUIDE

## Quick Reference for Your New Premium Theme

---

## 🎯 COLOR PALETTE

### Primary Colors
```
⚫ Dark Background     #0a0a0a  ████████
⚫ Darker Background   #000000  ████████
⬛ Card Background     #1a1a1a  ████████
⬛ Card Hover          #252525  ████████
```

### Accent Colors
```
🟨 Gold Primary        #d4af37  ████████
🟡 Gold Hover          #f0c947  ████████
🟢 Success Green       #00ff88  ████████
⭕ Border Gray         #333333  ████████
```

### Text Colors
```
⚪ Primary Text        #ffffff  ████████ (Headings)
◽ Secondary Text      #b3b3b3  ████████ (Body)
◾ Muted Text          #666666  ████████ (Subtle)
```

---

## 📏 SPACING SCALE

```
XS:    8px   ▪
SM:    12px  ▪▪
MD:    20px  ▪▪▪
LG:    30px  ▪▪▪▪
XL:    40px  ▪▪▪▪▪
XXL:   60px  ▪▪▪▪▪▪
XXXL:  80px  ▪▪▪▪▪▪▪
```

---

## 🔤 TYPOGRAPHY

### Font Sizes
```
Hero:         64px  ████████████
Page Title:   42px  ██████████
Section:      36px  ████████
Card Title:   24px  ██████
Body:         16px  ████
Small:        14px  ███
Tiny:         12px  ██
```

### Font Weights
```
Black:    900  ████████████
Bold:     700  █████████
Semibold: 600  ████████
Medium:   500  ███████
Regular:  400  ██████
```

---

## ✨ ANIMATIONS

### 1. fadeInUp (Cards)
```
⬇️ Start:  Invisible, 40px below
⬆️ End:    Visible, normal position
⏱️ Time:   0.6 seconds
🎨 Effect: Smooth fade + slide up
```

### 2. Hover Lift (Cards)
```
📦 Start:  Normal position
⬆️ Hover:  Lifts -10px up
🎨 Scale:  Grows to 102%
🌟 Glow:   Gold shadow appears
⏱️ Time:   0.4 seconds
```

### 3. Pulse (Hero)
```
💓 Pattern: Breathe in/out
⏱️ Speed:   8 seconds loop
🎨 Effect:  Scale 1.0 → 1.1 → 1.0
```

---

## 🎯 COMPONENT STYLES

### Buttons
```
┌─────────────────────────┐
│   🟨 AJOUTER AU PANIER  │  Primary
└─────────────────────────┘

┌─────────────────────────┐
│   ⚪ RÉINITIALISER      │  Secondary
└─────────────────────────┘
```

### Cards
```
┌─────────────────────────┐
│  🖼️  Image (280x260)    │
│  ┌──────────────────┐   │
│  │  🟢 EN STOCK     │   │
│  └──────────────────┘   │
│                          │
│  🟡 LED                  │
│  Product Name Here       │
│  ⚪ 199.99 DH            │
│  ┌──────────────────┐   │
│  │  🟨 AJOUTER      │   │
│  └──────────────────┘   │
└─────────────────────────┘
```

### Forms
```
┌─────────────────────────┐
│  📧  votre@email.com    │  Input
└─────────────────────────┘

┌─────────────────────────┐
│  🔒  ••••••••••         │  Password
└─────────────────────────┘
```

---

## 🎨 DESIGN PATTERNS

### Glassmorphism (Login/Register)
```
╔═══════════════════════╗
║ ░░░░░░░░░░░░░░░░░░░░ ║  Semi-transparent
║ ░░ EKOLED ░░░░░░░░░░ ║  Blur: 20px
║ ░░░░░░░░░░░░░░░░░░░░ ║  Border: Gold glow
║ ░░ [Input] ░░░░░░░░░ ║
║ ░░ [Input] ░░░░░░░░░ ║
║ ░░ [Button] ░░░░░░░░ ║
╚═══════════════════════╝
```

### Product Grid
```
┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐
│ 💡  │ │ 💡  │ │ 💡  │ │ 💡  │
│ LED  │ │ LED  │ │ LED  │ │ LED  │
└──────┘ └──────┘ └──────┘ └──────┘

┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐
│ 💡  │ │ 💡  │ │ 💡  │ │ 💡  │
│ LED  │ │ LED  │ │ LED  │ │ LED  │
└──────┘ └──────┘ └──────┘ └──────┘
```

---

## 🎪 PAGE LAYOUTS

### Homepage Structure
```
╔════════════════════════════════════╗
║  🟨 EKOLED    [NAV]    🛒 👤      ║  Header
╠════════════════════════════════════╣
║                                    ║
║     🌟 Éclairage LED Premium      ║  Hero
║        [EXPLORER]                  ║
║                                    ║
╠════════════════════════════════════╣
║  NOS CATÉGORIES                   ║
║  ┌─────┐ ┌─────┐ ┌─────┐         ║  Categories
║  │ LED │ │ LED │ │ LED │         ║
║  └─────┘ └─────┘ └─────┘         ║
╠════════════════════════════════════╣
║  PRODUITS POPULAIRES              ║
║  ┌────┐ ┌────┐ ┌────┐ ┌────┐    ║  Products
║  │💡 │ │💡 │ │💡 │ │💡 │    ║
║  └────┘ └────┘ └────┘ └────┘    ║
╠════════════════════════════════════╣
║  [FOOTER - 4 Columns]             ║  Footer
╚════════════════════════════════════╝
```

### Series Page with Filters
```
╔════════════════════════════════════╗
║  Header + Breadcrumb               ║
╠═════╦══════════════════════════════╣
║  F  ║  Toolbar (Sort/View)        ║
║  I  ╠══════════════════════════════╣
║  L  ║  ┌────┐ ┌────┐ ┌────┐      ║
║  T  ║  │💡 │ │💡 │ │💡 │      ║
║  E  ║  └────┘ └────┘ └────┘      ║
║  R  ║  ┌────┐ ┌────┐ ┌────┐      ║
║  S  ║  │💡 │ │💡 │ │💡 │      ║
║     ║  └────┘ └────┘ └────┘      ║
╚═════╩══════════════════════════════╝
```

---

## 🎭 HOVER STATES

### Before Hover
```
┌───────────────┐
│               │
│   Product     │
│               │
└───────────────┘
```

### On Hover
```
      ⬆️ -10px
┌───────────────┐  🌟 Gold Glow
│     ⬆️        │
│   Product     │  🔍 Image Zoom
│               │
└───────────────┘  ✨ Scale 102%
```

---

## 📱 RESPONSIVE BEHAVIOR

### Desktop (>768px)
```
[Logo]  [Nav Nav Nav Nav]  [Icons]
```

### Mobile (<768px)
```
[Logo      ]
[Nav       ]
[Nav       ]
[Nav       ]
[Icons     ]
```

---

## 🎨 ICON USAGE

### Common Icons
```
🏠 Home          fa-home
📦 Products      fa-box
🛒 Cart          fa-shopping-cart
👤 User          fa-user
🔒 Lock          fa-lock
📧 Email         fa-envelope
✓  Check         fa-check
✕  Close         fa-times
⚙️ Settings      fa-cog
📤 Logout        fa-sign-out-alt
🎯 Category      fa-th
🔍 Search        fa-search
📞 Phone         fa-phone
📍 Location      fa-map-marker-alt
```

---

## 🎯 CALL-TO-ACTIONS

### Primary Actions
```
┌──────────────────────┐
│  AJOUTER AU PANIER   │  Gold background
└──────────────────────┘  Black text

┌──────────────────────┐
│  ACHETER MAINTENANT  │  Gold background
└──────────────────────┘  Black text
```

### Secondary Actions
```
┌──────────────────────┐
│  VOIR PLUS           │  Transparent
└──────────────────────┘  Gold border

┌──────────────────────┐
│  ANNULER             │  Gray background
└──────────────────────┘  White text
```

---

## 🌟 SPECIAL EFFECTS

### Gold Glow
```
box-shadow: 0 0 20px rgba(212,175,55,0.5)
```

### Card Shadow Levels
```
Level 1: 0 2px 8px rgba(0,0,0,0.3)   ▁
Level 2: 0 4px 16px rgba(0,0,0,0.4)  ▂
Level 3: 0 8px 32px rgba(0,0,0,0.5)  ▃
```

### Backdrop Blur
```
backdrop-filter: blur(20px)
background: rgba(26, 26, 26, 0.8)
```

---

## 🎬 ANIMATION TIMING

```
Instant:     0.15s  ▪
Fast:        0.3s   ▪▪
Normal:      0.4s   ▪▪▪
Smooth:      0.6s   ▪▪▪▪
Slow:        0.8s   ▪▪▪▪▪
```

### Easing Functions
```
ease-out:     Natural deceleration
ease-in-out:  Smooth both ways
cubic-bezier: Custom curves
```

---

## ✅ DESIGN CHECKLIST

### Every Page Should Have:
- ✅ Dark background (#0a0a0a)
- ✅ Gold accents (#d4af37)
- ✅ Consistent header
- ✅ Professional footer
- ✅ Smooth animations
- ✅ Hover effects
- ✅ Mobile responsive
- ✅ Loading states

### Every Card Should Have:
- ✅ Border (#333333)
- ✅ Hover lift effect
- ✅ Image zoom on hover
- ✅ Rounded corners (16px)
- ✅ Proper padding (25-30px)

### Every Button Should Have:
- ✅ Gold background (primary)
- ✅ Hover lift (-2px)
- ✅ Shadow on hover
- ✅ Uppercase text
- ✅ Letter spacing (0.5-2px)

---

## 🎨 CUSTOM CSS VARIABLES

Copy these for new pages:
```css
:root {
    --bg-dark: #0a0a0a;
    --bg-darker: #000000;
    --bg-card: #1a1a1a;
    --bg-card-hover: #252525;
    --text-primary: #ffffff;
    --text-secondary: #b3b3b3;
    --text-muted: #666666;
    --accent-gold: #d4af37;
    --accent-gold-hover: #f0c947;
    --border-color: #333333;
    --success-green: #00ff88;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.3);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.4);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.5);
}
```

---

## 🎭 STATES REFERENCE

### Normal
```
Background: var(--bg-card)
Border: var(--border-color)
Text: var(--text-primary)
```

### Hover
```
Background: var(--bg-card-hover)
Border: var(--accent-gold)
Transform: translateY(-10px)
Shadow: Gold glow
```

### Active
```
Transform: translateY(0)
Shadow: Reduced
```

### Disabled
```
Background: var(--text-muted)
Cursor: not-allowed
Opacity: 0.6
```

---

*Design System: EKOLED Premium v2.0*
*Last Updated: October 18, 2025*
