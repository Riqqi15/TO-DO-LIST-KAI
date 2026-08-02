<?php

namespace App\Http\Controllers\Workspace;

use App\Domain\Workspace\Actions\CreateTeam;
use App\Domain\Workspace\Actions\DeleteTeam;
use App\Domain\Workspace\Actions\GenerateTeamInviteCode;
use App\Domain\Workspace\Actions\JoinTeam;
use App\Domain\Workspace\Actions\LeaveTeam;
use App\Domain\Workspace\Actions\RemoveTeamMember;
use App\Domain\Workspace\Actions\TransferTeamOwnership;
use App\Domain\Workspace\Actions\UpdateTeamCapacity;
use App\Domain\Workspace\Models\Workspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\DeleteTeamRequest;
use App\Http\Requests\Workspace\JoinTeamRequest;
use App\Http\Requests\Workspace\StoreTeamRequest;
use App\Http\Requests\Workspace\TransferOwnershipRequest;
use App\Http\Requests\Workspace\UpdateTeamCapacityRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function store(StoreTeamRequest $request, CreateTeam $action): RedirectResponse
    {
        $workspace = $action->handle($request->user(), $request->validated('name'));

        return back()->with('success', 'Tim berhasil dibuat.')->with('workspace_id', $workspace->id);
    }

    public function join(JoinTeamRequest $request, JoinTeam $action): RedirectResponse
    {
        $workspace = $action->handle($request->user(), $request->validated('code'));

        return back()->with('success', 'Berhasil bergabung ke tim.')->with('workspace_id', $workspace->id);
    }

    public function generateInvite(Request $request, Workspace $workspace, GenerateTeamInviteCode $action): RedirectResponse
    {
        $invite = $action->handle($workspace, $request->user());

        return back()->with('success', 'Kode tim berlaku selama 5 menit.')->with('team_invite', [
            'code' => $invite['code'],
            'expires_at' => $invite['expires_at'],
            'workspace_id' => $workspace->id,
        ]);
    }

    public function capacity(UpdateTeamCapacityRequest $request, Workspace $workspace, UpdateTeamCapacity $action): RedirectResponse
    {
        $action->handle($workspace, $request->user(), $request->integer('member_limit'));

        return back()->with('success', 'Kapasitas tim diperbarui.');
    }

    public function transfer(TransferOwnershipRequest $request, Workspace $workspace, TransferTeamOwnership $action): RedirectResponse
    {
        $action->handle($workspace, $request->user(), User::findOrFail($request->integer('user_id')));

        return back()->with('success', 'Kepemilikan tim berhasil dipindahkan.');
    }

    public function removeMember(Request $request, Workspace $workspace, User $user, RemoveTeamMember $action): RedirectResponse
    {
        $action->handle($workspace, $request->user(), $user);

        return back()->with('success', 'Anggota dikeluarkan dari tim.');
    }

    public function leave(Request $request, Workspace $workspace, LeaveTeam $action): RedirectResponse
    {
        $action->handle($workspace, $request->user());

        return back()->with('success', 'Anda telah keluar dari tim.');
    }

    public function destroy(DeleteTeamRequest $request, Workspace $workspace, DeleteTeam $action): RedirectResponse
    {
        $action->handle($workspace, $request->user(), $request->validated('confirmation'));

        return redirect()->route('todo.index')->with('success', 'Tim dan seluruh data operasionalnya dihapus permanen.');
    }
}
