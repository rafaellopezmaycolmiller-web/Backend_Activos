<?php
namespace App\Policies;
use App\Models\ReconciliationRun; use App\Models\User;
class ReconciliationRunPolicy { public function viewAny(User $u): bool{return $u->hasPermission('reconciliation.view');} public function view(User $u, ReconciliationRun $r): bool{return $u->hasPermission('reconciliation.view') && ($u->hasGlobalPermission('reconciliation.view') || $u->area_id===$r->area_id || $r->area_id===null);} public function create(User $u): bool{return $u->hasPermission('reconciliation.create');} }
