import { Link } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    BriefcaseBusiness,
    CalendarDays,
    FileText,
    FolderGit2,
    KeyRound,
    LayoutGrid,
    Users,
} from 'lucide-react';

import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Office Management',
        href: '/offices',
        icon: Building2,
    },
    {
        title: 'Service Management',
        href: '/services',
        icon: BriefcaseBusiness,
    },
    {
        title: 'Eligible Employees',
        href: '/eligible-employees',
        icon: Users,
    },
    {
        title: 'Activation Codes',
        href: '/activation-codes',
        icon: KeyRound,
    },
    {
        title: 'Employee Password Reset',
        href: '/employee-reset-codes',
        icon: KeyRound,
    },

    {
        title: 'Transfer Cycles',
        href: '/transfer-cycles',
        icon: CalendarDays,
    },
    {
        title: 'My Transfer Applications',
        href: '/transfer-applications',
        icon: FileText,
    },
    {
        title: 'Office Head Assignments',
        href: '/office-reviewer-assignments',
        icon: Users,
    },

    {
        title: 'Office Review Inbox',
        href: '/office-reviews',
        icon: FileText,
    },
    {
        title: 'User Management',
        href: '/user-management',
        icon: Users,
    },
    {
        title: 'District Review Inbox',
        href: '/district-reviews',
        icon: FileText,
    },

    {
        title: 'Mutual Transfer Preview',
        href: '/mutual-transfers',
        icon: Users,
    },

    {
        title: 'Management Reports',
        href: '/reports',
        icon: FileText,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
