# Project Notes for Claude

## VPS frontend deploy
- This app uses Laravel + Inertia + Vite. After `git pull` on VPS, React/TSX changes are not visible until the production bundle is rebuilt.
- Use `yarn build` or `npm run build`, then `php artisan optimize:clear`.
- `composer run dev` runs `yarn dev` for local development only. Do not use it as the VPS production fix.
- If the browser still shows old UI after build, hard refresh or test in an incognito tab.

## Service order approve vs edit UI
- In `resources/js/pages/service-order/index.tsx`, the approve/confirm service order modal must not show `Thong tin fanpage` or `Thong tin website`.
- Keep those fields in edit flows:
  - single-account edit inside `resources/js/pages/service-order/index.tsx`
  - multi-account edit inside `resources/js/pages/service-order/components/AccountFormEdit.tsx`
