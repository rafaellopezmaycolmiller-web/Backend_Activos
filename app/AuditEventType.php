<?php

namespace App;

enum AuditEventType: string
{
    case LoginSucceeded = 'auth.login.succeeded';
    case LoginFailed = 'auth.login.failed';
    case MfaChallengeIssued = 'auth.mfa.challenge_issued';
    case MfaFailed = 'auth.mfa.failed';
    case Logout = 'auth.logout';
    case PasswordResetRequested = 'auth.password_reset.requested';
    case PasswordResetCompleted = 'auth.password_reset.completed';
    case AccessRecoveryRequested = 'auth.access_recovery.requested';
    case AccessRecoveryApproved = 'auth.access_recovery.approved';
    case AccessRecoveryRejected = 'auth.access_recovery.rejected';
    case UserCreated = 'identity.user.created';
    case UserUpdated = 'identity.user.updated';
    case AreaCreated = 'organization.area.created';
    case AreaUpdated = 'organization.area.updated';
    case AssetCreated = 'assets.asset.created';
    case AssetUpdated = 'assets.asset.updated';
    case AssetDocumentUploaded = 'assets.document.uploaded';
    case MovementRequested = 'movements.requested';
    case MovementApproved = 'movements.approved';
    case MovementRejected = 'movements.rejected';
    case MovementReceived = 'movements.received';
    case CustodyAssigned = 'custody.assigned';
    case InventoryCampaignCreated = 'inventory.campaign_created';
    case InventoryCampaignStarted = 'inventory.campaign_started';
    case InventoryCampaignClosed = 'inventory.campaign_closed';
    case InventoryCheckRecorded = 'inventory.check_recorded';
    case ReconciliationCreated = 'reconciliation.created';
    case ReconciliationDifferenceUpdated = 'reconciliation.difference_updated';
    case DisposalRequested = 'disposals.requested';
    case DisposalApproved = 'disposals.approved';
    case DisposalRejected = 'disposals.rejected';
    case DisposalReviewed = 'disposals.reviewed';
    case EvaluationRequested = 'evaluations.requested';
    case EvaluationAssigned = 'evaluations.assigned';
    case EvaluationCompleted = 'evaluations.completed';
    case EvaluationDocumentUploaded = 'evaluations.document_uploaded';
    case DisposalDocumentUploaded = 'disposals.document_uploaded';
    case ReportExported = 'reports.exported';
}
