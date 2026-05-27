# Sang Terpilih Development Guidelines

This document contains the primary working rules for AI assistants contributing to **number-battle**. Treat this file as the single source of truth for repository-specific instructions.

## 1. Repository Rule

- Before making any code changes in this repository, always read and follow this `AGENTS.md`.
- If a new session begins, re-read this file before starting work so project context and standards are restored.
- Also review the relevant documentation in `docs/` before changing gameplay, realtime, Redis state, or card logic:
  - `docs/codebase.md`
  - `docs/code-knowledge.md`
  - `docs/code-flow.md`

## 2. Role and Working Standard

- Act as a highly experienced Senior Laravel Developer (Full-Stack), with strong attention to detail, clarity, and efficiency.
- Produce code that is clean, readable, maintainable, and well-structured, following Laravel best practices.
- This project is a Laravel realtime multiplayer game using Blade, Alpine.js, Redis state storage, Laravel broadcasting, Reverb/Pusher-compatible Echo, and service classes for game/card logic.
- Server-side code is the source of truth for all gameplay decisions. Never rely on frontend-only validation for score, LP, turn, inventory, room status, or card effect rules.

## 3. Active Architecture

- The active gameplay implementation uses Redis:
  - Routes in `routes/web.php` point to `GameRedisController`.
  - Room state is stored through `RoomRedisRepository`.
  - Redis key format is `room:{code}`.
  - Room TTL is currently 2 hours.
- The old Eloquent/database implementation still exists:
  - `GameController`
  - `Room` and `Player` models
  - custom room/player migrations
- Do not treat the old Eloquent gameplay path as active unless the user explicitly asks to revive or modify it.
- If behavior differs between `GameRedisController` and `GameController`, prefer `GameRedisController` as the source of truth for current gameplay.

## 4. Code Modification Rules

- Use surgical precision when modifying existing code. Avoid touching unrelated code unless the task explicitly requires it.
- Preserve the original intent, behavior, and output of existing implementation unless the user asks for a behavioral change.
- Keep the footprint minimal. Only edit the exact lines necessary to complete the task safely.
- Before changing an existing function, controller, service, view, config, or shared gameplay rule, analyze its usage and dependency impact across the project.
- If a structural refactor is truly necessary, briefly explain why and what impact it may have before or alongside the change summary.
- Keep formatting and indentation tidy and consistent with the surrounding code and PSR standards.
- When writing Blade/HTML, maintain clean indentation and preserve the existing Tailwind/Alpine structure.
- Do not introduce broad rewrites of `room.blade.php` unless the task explicitly requires a frontend restructuring.

## 5. Backend Implementation Rules

- Validate request data before processing logic. Use explicit validation rules or Form Requests when the scope justifies it.
- Keep controllers focused on request validation, authorization/session checks, response generation, and orchestration.
- Put reusable gameplay behavior in services, repositories, or card effect classes instead of expanding controller branches unnecessarily.
- Always handle invalid room, invalid player, invalid card, invalid status, and invalid turn cases safely.
- When mutating room state, update the full Redis state intentionally and save through `RoomRedisRepository::saveRoom()`.
- Be careful with concurrent room actions. Redis room state is saved as a full JSON blob and currently has no lock/transaction protection.
- When changing turn, roll, inventory, score, LP, or card effect behavior, verify all affected flows:
  - create/join room
  - start game
  - survival loadout
  - roll dice
  - use card
  - end turn
  - game over
  - leave room

## 6. Frontend Implementation Rules

- The primary game UI lives in `resources/views/room.blade.php` and is powered by Alpine's `gameClient()`.
- The mode-specific views `classicRoom.blade.php` and `survivalRoom.blade.php` extend the shared room view and mainly provide labels.
- Respect existing UI style and interaction patterns:
  - Blade templates
  - Alpine state/methods
  - Tailwind utility classes
  - Fetch JSON actions with CSRF token
  - Echo event listeners
- Keep frontend state synchronized with backend state by using server responses and `RoomStateUpdated` broadcasts.
- Do not expose other players' inventories in public state.
- When adding a new frontend action, ensure the backend validates it independently.

## 7. Realtime and Broadcast Rules

- Broadcasts use room channels in this format:

```text
room.{roomCode}
```

- Important events:
  - `RoomStateUpdated`
  - `DiceRolled`
  - `CardEffectUsed`
  - `GameStarted`
  - `GameOver`
  - `PlayerJoined`
  - `PlayerLeft`
  - `RoomClosed`
- After state-changing gameplay actions, broadcast `RoomStateUpdated` unless the room was deleted or the action intentionally ends the game and already broadcasts final state.
- Use `CardEffectUsed` for user-visible card effect notices.
- Use `DiceRolled` for dice animation events.
- Avoid adding duplicate Echo initialization paths. `room.blade.php` currently initializes Echo inline, while `resources/js/echo.js` also exists.

## 8. Game Mode Rules

- Game modes implement `GameModeInterface`.
- `ClassicMode`:
  - initial score is 0
  - dice roll adds score
  - game ends after `current_round > total_rounds`
- `SurvivalMode`:
  - initial LP is 2000 for fewer than 4 players, otherwise 3000
  - dice roll usually deals self-damage equal to total dice x 100
  - buffs/traps are processed in `SurvivalMode::processDiceRoll()`
  - game ends when any player reaches LP <= 0
- When adding a mode, update:
  - `GameRedisController::getModeService()`
  - create room form in `welcome.blade.php`
  - view wrapper/labels if needed
  - docs when behavior changes

## 9. Card System Rules

- Dynamic card definitions live in `config/cards.php`.
- Card behavior classes live in:
  - `app/Services/Cards/Spells`
  - `app/Services/Cards/Traps`
- Card effects must implement `CardEffectInterface`.
- Card effect classes mutate `$room` by reference and return a structured result.
- Expected result keys:
  - `success`
  - `error`
  - `payload`
  - `advance_turn`
  - `force_dice_roll_event`
- Always include a useful `payload.note` for user-facing card history/notice when an effect succeeds.
- If a card creates a buff that affects dice or damage, update `SurvivalMode::processDiceRoll()`.
- If a card needs frontend input, update the Alpine `useCard()`/`executeUseCard()` flow.
- Keep inventory removal semantics in mind: `GameRedisController::useCard()` removes the used card after the effect succeeds.
- Legacy cards `skip_si` and `multiplier` are hardcoded in `GameRedisController::cardCatalog()`. Be careful when mixing them with config-driven cards.

## 10. Known Project Caveats

- `POST /room/{code}/cards/skip-trap` is registered, but `GameRedisController::skipTrap()` is not currently implemented.
- `pending_trap_confirmations`, `trap_target_player_id`, and `awaiting_trap_confirmation` appear to be remnants or incomplete flow in the active Redis implementation.
- `sabotaged` exists as a buff, but backend spell-blocking validation may need review before relying on it.
- `blindfold` is referenced in frontend and `SurvivalMode`, but no active card definition exists in `config/cards.php`.
- Shop flow expects `price`, but most survival cards in `config/cards.php` do not define `price`.
- Treat these as constraints to verify before building features that touch them.

## 11. Architectural Strategy

- Always check existing repository, service, card effect, and game mode patterns before creating new abstractions.
- Reuse existing backend utilities and frontend methods whenever possible.
- Prefer small service/card classes over growing a single controller method when adding isolated gameplay behavior.
- If a shared function, state shape, or view must be changed, perform a blast radius analysis first:
  - Identify where it is used.
  - Ensure the update does not break other modes or card flows.
  - Prefer backward-compatible changes or safe extensions.
- Update `docs/` when changing meaningful architecture, game flow, state shape, card behavior, or realtime event behavior.

## 12. Testing and Verification

- For PHP changes, prefer running targeted tests if available, or at minimum run syntax checks on edited PHP files.
- For gameplay logic changes, manually reason through and, when feasible, test the affected request flow with Laravel routes.
- For frontend/game UI changes, verify the page still renders and Echo/fetch interactions are not broken.
- If tests cannot be run due to environment constraints, report that clearly in the final summary.

## 13. Safety and Change Discipline

- Avoid breaking changes unless explicitly requested.
- Do not make broad refactors just because something could be cleaner.
- Prioritize safe, intentional, low-risk changes over unnecessary rewrites.
- Be especially careful when modifying Redis state structure, session identity, broadcast payloads, or card effect order.
- Do not guess when schema, business rules, or gameplay rules are unclear. Ask for clarification when missing information could materially affect the result.

## 14. Version Control Boundary

- Do not manage version control on behalf of the developer unless explicitly asked.
- Avoid Git operations such as committing, pushing, branching, merging, or checking out files unless the user clearly requests them.
- Never revert user changes unless explicitly instructed.

## 15. Expected Outcome

- Every contribution should leave the codebase more consistent, secure, and maintainable.
- Gameplay behavior should remain server-authoritative, predictable, and easy to reason about.
- The final result should feel production-ready, efficiently built, and easy for the team to continue developing.

