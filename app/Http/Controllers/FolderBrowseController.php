<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FolderBrowseController extends Controller
{
    public function show(Request $request, Folder $folder, TransactionScopeService $scope): View
    {
        $user = $request->user();

        if (! $folder->is_active || ! $user->canAccessFolder($folder)) {
            abort(403);
        }

        $children = Folder::scopedQuery($user->folderOrgScopeDepartmentIds())
            ->where('parent_id', $folder->id)
            ->where('is_active', true)
            ->withCount(['transactions as visible_transactions_count' => function ($query) use ($scope, $user) {
                $scope->applyScopeToQuery($query, $user);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $transactions = Transaction::query();
        $scope->applyScopeToQuery($transactions, $user);

        $transactions = $transactions
            ->with(['department', 'transactionType', 'status'])
            ->where('folder_id', $folder->id)
            ->latest()
            ->paginate(15);

        return view('folders.browse', [
            'folder' => $folder,
            'ancestors' => $this->ancestors($folder, $user),
            'children' => $children,
            'transactions' => $transactions,
        ]);
    }

    /** @return list<Folder> */
    private function ancestors(Folder $folder, User $user): array
    {
        $chain = [];
        $parentId = $folder->parent_id;
        $guard = 0;

        while ($parentId && $guard < 30) {
            $parent = Folder::query()->find($parentId);

            if (! $parent || ! $parent->is_active || ! $user->canAccessFolder($parent)) {
                break;
            }

            array_unshift($chain, $parent);
            $parentId = $parent->parent_id;
            $guard++;
        }

        return $chain;
    }
}
