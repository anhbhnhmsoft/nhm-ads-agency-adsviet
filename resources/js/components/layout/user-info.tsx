import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { IUser } from '@/lib/types/type';

export function UserInfo({
    user,
}: {
    user: IUser | null;
}) {
    const getInitials = useInitials();
    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                {/*<AvatarImage src={user.avatar || ''} alt={user.name} />*/}
                <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                    {getInitials(user?.name || 'User')}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{user?.name || 'User'}</span>
                <span className="truncate text-xs">
                        {user?.username || 'user@example.com'}
                </span>
            </div>
        </>
    );
}
