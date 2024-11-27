export interface ZenMoneyTag {
  id: string;
  changed: number;
  user: number;
  title: string;
  parent?: string;
  showIncome: boolean;
  showOutcome: boolean;
  budgetIncome: boolean;
  budgetOutcome: boolean;
  required?: boolean;
  icon?: string;
  picture?: string;
  color?: number;
}

export interface ExpenseCategory {
  id: string;
  title: string;
  parentId?: string;
  icon?: string;
  color?: string;
}
