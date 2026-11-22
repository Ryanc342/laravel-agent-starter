import { Avatar, AvatarFallback } from '@/components/ui/avatar';

export function UserInfo({
    user,
    showEmail = false,
}: {
    user: App.Http.Resources.UserResource;
    showEmail?: boolean;
}) {
    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarFallback className="rounded-lg bg-muted text-foreground">
                    {user.initials}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{user.name}</span>
                {showEmail && (
                    <span className="truncate text-xs text-muted-foreground">
                        {user.email}
                    </span>
                )}
            </div>
        </>
    );
}
